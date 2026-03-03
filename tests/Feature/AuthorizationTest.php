<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function createCustomer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        return $user;
    }

    private function validProductPayload(?Category $category = null): array
    {
        $category ??= Category::factory()->create();

        return [
            'name' => 'Produto '.uniqid(),
            'description' => 'Descrição válida do produto.',
            'price' => 99.90,
            'quantity' => 10,
            'category_id' => $category->id,
        ];
    }

    // ── Guest access restrictions ─────────────────────────────────────────────

    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
        $this->postJson('/api/v1/auth/logout')->assertStatus(401);
        $this->getJson('/api/v1/cart')->assertStatus(401);
        $this->postJson('/api/v1/cart/items')->assertStatus(401);
        $this->getJson('/api/v1/orders')->assertStatus(401);
        $this->postJson('/api/v1/orders')->assertStatus(401);
    }

    public function test_guest_can_access_public_routes(): void
    {
        Product::factory()->create();
        $product = Product::factory()->create();
        $category = Category::factory()->create();

        $this->getJson('/api/v1/products')->assertStatus(200);
        $this->getJson("/api/v1/products/{$product->id}")->assertStatus(200);
        $this->getJson('/api/v1/categories')->assertStatus(200);
        $this->getJson("/api/v1/categories/{$category->id}")->assertStatus(200);
    }

    // ── Customer cannot access admin-only endpoints ───────────────────────────

    public function test_customer_cannot_create_product(): void
    {
        $customer = $this->createCustomer();
        $payload = $this->validProductPayload();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/products', $payload)
            ->assertStatus(403);
    }

    public function test_customer_cannot_update_product(): void
    {
        $customer = $this->createCustomer();
        $product = Product::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", ['name' => 'Novo Nome'])
            ->assertStatus(403);
    }

    public function test_customer_cannot_delete_product(): void
    {
        $customer = $this->createCustomer();
        $product = Product::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertStatus(403);
    }

    public function test_customer_cannot_view_low_stock(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/products/low-stock')
            ->assertStatus(403);
    }

    public function test_customer_cannot_update_order_status(): void
    {
        $customer = $this->createCustomer();
        $order = Order::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertStatus(403);
    }

    // ── Admin can access all endpoints ───────────────────────────────────────

    public function test_admin_can_create_product(): void
    {
        $admin = $this->createAdmin();
        $payload = $this->validProductPayload();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/products', $payload)
            ->assertStatus(201);
    }

    public function test_admin_can_update_product(): void
    {
        $admin = $this->createAdmin();
        $product = Product::factory()->create(['name' => 'Produto Original']);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", ['name' => 'Produto Atualizado'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Produto Atualizado');
    }

    public function test_admin_can_delete_product(): void
    {
        $admin = $this->createAdmin();
        $product = Product::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_admin_can_view_low_stock(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/products/low-stock')
            ->assertStatus(200);
    }

    public function test_admin_can_update_any_order_status(): void
    {
        $admin = $this->createAdmin();
        $order = Order::factory()->pending()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'processing'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'processing');
    }

    // ── Resource isolation ────────────────────────────────────────────────────

    public function test_customer_cannot_view_another_users_order(): void
    {
        $customer1 = $this->createCustomer();
        $customer2 = $this->createCustomer();
        $order = Order::factory()->create(['user_id' => $customer2->id]);

        $this->actingAs($customer1, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertStatus(404);
    }

    public function test_customer_order_list_is_isolated(): void
    {
        $customer1 = $this->createCustomer();
        $customer2 = $this->createCustomer();
        Order::factory()->count(3)->create(['user_id' => $customer1->id]);
        Order::factory()->count(2)->create(['user_id' => $customer2->id]);

        $response = $this->actingAs($customer1, 'sanctum')
            ->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_customer_can_only_update_their_own_cart_items(): void
    {
        $customer1 = $this->createCustomer();
        $customer2 = $this->createCustomer();
        $product = Product::factory()->create(['quantity' => 10]);
        $cart = \App\Models\Cart::factory()->create(['user_id' => $customer1->id]);
        $item = \App\Models\CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

        $this->actingAs($customer2, 'sanctum')
            ->putJson("/api/v1/cart/items/{$item->id}", ['quantity' => 5])
            ->assertStatus(404);
    }

    // ── Rate limiting ──────────────────────────────────────────────────────────────

    public function test_api_rate_limiting_returns_429_when_limit_exceeded(): void
    {
        Product::factory()->count(3)->create(['active' => true]);

        // Use a unique key per test so accumulated hits from other tests don't interfere
        $uniqueKey = 'rate-limit-test-'.uniqid();
        RateLimiter::for('api', fn ($request) => Limit::perMinute(3)->by($uniqueKey));

        for ($i = 0; $i < 3; $i++) {
            $this->getJson('/api/v1/products')->assertStatus(200);
        }

        $this->getJson('/api/v1/products')->assertStatus(429);

        // Restore using user-based key limit so other tests are not affected
        RateLimiter::for('api', fn ($request) => Limit::perMinute(1000)->by(
            $request->user()?->id ?: $request->ip()
        ));
    }

    public function test_authenticated_user_does_not_exceed_rate_limit_normally(): void
    {
        $customer = $this->createCustomer();

        // With a high per-user limit, authenticated users should not be rate limited
        RateLimiter::for('api', fn ($request) => Limit::perMinute(100)->by(
            $request->user()?->id ?: $request->ip()
        ));

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($customer, 'sanctum')
                ->getJson('/api/v1/orders')
                ->assertStatus(200);
        }

        RateLimiter::for('api', fn ($request) => Limit::perMinute(1000)->by(
            $request->user()?->id ?: $request->ip()
        ));
    }

    public function test_authenticated_user_rate_limit_uses_user_id_as_key(): void
    {
        $customer = $this->createCustomer();

        // Set limit to 3 per minute scoped to user ID
        RateLimiter::for('api', fn ($request) => Limit::perMinute(3)->by(
            $request->user()?->id ?: $request->ip()
        ));

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($customer, 'sanctum')
                ->getJson('/api/v1/orders')
                ->assertStatus(200);
        }

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/orders')
            ->assertStatus(429);

        RateLimiter::for('api', fn ($request) => Limit::perMinute(1000)->by(
            $request->user()?->id ?: $request->ip()
        ));
    }
}
