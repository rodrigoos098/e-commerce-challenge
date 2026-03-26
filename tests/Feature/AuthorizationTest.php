<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
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

    private function validOrderAddress(): array
    {
        return [
            'street' => '123 Main Street',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip_code' => '01234-567',
            'country' => 'BR',
        ];
    }

    private function seedCartForOrder(User $user): void
    {
        $product = Product::factory()->create(['quantity' => 10, 'active' => true, 'price' => 50.0]);
        $cart = Cart::factory()->create(['user_id' => $user->id]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    // ── Guest access restrictions ─────────────────────────────────────────────

    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);

        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);

        $this->getJson('/api/v1/cart')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);

        $this->postJson('/api/v1/cart/items')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);

        $this->getJson('/api/v1/orders')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);

        $this->postJson('/api/v1/orders')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
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
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_customer_cannot_update_product(): void
    {
        $customer = $this->createCustomer();
        $product = Product::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", ['name' => 'Novo Nome'])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_customer_cannot_delete_product(): void
    {
        $customer = $this->createCustomer();
        $product = Product::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_api_product_create_route_enforces_product_policy_create(): void
    {
        $admin = $this->createAdmin();
        $payload = $this->validProductPayload();

        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if ($ability === 'create' && $arguments === [Product::class]) {
                return false;
            }

            return null;
        });

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/products', $payload)
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_api_product_update_route_enforces_product_policy_update(): void
    {
        $admin = $this->createAdmin();
        $product = Product::factory()->create();

        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if ($ability === 'update' && count($arguments) === 1 && $arguments[0] instanceof Product) {
                return false;
            }

            return null;
        });

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", ['name' => 'Produto Bloqueado'])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_api_product_delete_route_enforces_product_policy_delete(): void
    {
        $admin = $this->createAdmin();
        $product = Product::factory()->create();

        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if ($ability === 'delete' && count($arguments) === 1 && $arguments[0] instanceof Product) {
                return false;
            }

            return null;
        });

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_customer_cannot_view_low_stock(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/products/low-stock')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_api_product_low_stock_route_enforces_product_policy_view_low_stock(): void
    {
        $admin = $this->createAdmin();

        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if ($ability === 'viewLowStock' && $arguments === [Product::class]) {
                return false;
            }

            return null;
        });

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/products/low-stock')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_customer_cannot_update_order_status(): void
    {
        $customer = $this->createCustomer();
        $order = Order::factory()->create();

        $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_api_order_status_route_enforces_order_policy_update(): void
    {
        $admin = $this->createAdmin();
        $order = Order::factory()->pending()->create();

        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if ($ability === 'update' && count($arguments) === 1 && $arguments[0] instanceof Order) {
                return false;
            }

            return null;
        });

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_web_order_status_route_enforces_order_policy_update(): void
    {
        $admin = $this->createAdmin();
        $order = Order::factory()->pending()->create();

        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if ($ability === 'update' && count($arguments) === 1 && $arguments[0] instanceof Order) {
                return false;
            }

            return null;
        });

        $this->actingAs($admin)
            ->put("/admin/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertForbidden();
    }

    public function test_api_order_create_route_enforces_order_policy_create(): void
    {
        $customer = $this->createCustomer();
        $this->seedCartForOrder($customer);
        $address = $this->validOrderAddress();

        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if ($ability === 'create' && $arguments === [Order::class]) {
                return false;
            }

            return null;
        });

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
            ])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_web_order_create_route_enforces_order_policy_create(): void
    {
        $customer = $this->createCustomer();
        $this->seedCartForOrder($customer);

        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if ($ability === 'create' && $arguments === [Order::class]) {
                return false;
            }

            return null;
        });

        $this->actingAs($customer)
            ->post('/customer/orders', [
                'shipping_name' => 'Cliente Teste',
                'shipping_street' => 'Rua Alfa, 10',
                'shipping_city' => 'Sao Paulo',
                'shipping_state' => 'SP',
                'shipping_zip' => '01000-000',
                'shipping_country' => 'BR',
                'same_billing' => true,
            ])
            ->assertForbidden();
    }

    public function test_customer_cannot_access_web_admin_mutation_routes(): void
    {
        $customer = $this->createCustomer();
        $product = Product::factory()->create();
        $order = Order::factory()->create();

        $this->actingAs($customer)
            ->post('/admin/products', $this->validProductPayload())
            ->assertForbidden();

        $this->actingAs($customer)
            ->put("/admin/products/{$product->id}", ['name' => 'Novo Nome'])
            ->assertForbidden();

        $this->actingAs($customer)
            ->delete("/admin/products/{$product->id}")
            ->assertForbidden();

        $this->actingAs($customer)
            ->put("/admin/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertForbidden();
    }

    public function test_customer_cannot_view_another_users_order_via_policy(): void
    {
        $customer1 = $this->createCustomer();
        $customer2 = $this->createCustomer();
        $order = Order::factory()->create(['user_id' => $customer2->id]);

        $this->actingAs($customer1, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_customer_cannot_view_another_users_web_order_via_policy(): void
    {
        $customer1 = $this->createCustomer();
        $customer2 = $this->createCustomer();
        $order = Order::factory()->create(['user_id' => $customer2->id]);

        $this->actingAs($customer1)
            ->get("/customer/orders/{$order->id}")
            ->assertForbidden();
    }

    public function test_admin_can_view_any_order_through_api_read_routes(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();
        $order = Order::factory()->create(['user_id' => $customer->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id);
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

    public function test_api_rate_limiter_is_configured_correctly(): void
    {
        // Read the production closure registered in bootstrap/app.php without overriding it.
        $closure = RateLimiter::limiter('api');

        $guestRequest = \Illuminate\Http\Request::create('/api/v1/products');
        $guestLimit = $closure($guestRequest);
        $this->assertEquals(100, $guestLimit->maxAttempts);
        $this->assertEquals(60, $guestLimit->decaySeconds);        // 1 minute
        $this->assertEquals($guestRequest->ip(), $guestLimit->key); // keyed by IP for guests

        $user = $this->createCustomer();
        $authRequest = \Illuminate\Http\Request::create('/api/v1/orders');
        $authRequest->setUserResolver(fn () => $user);
        $authLimit = $closure($authRequest);
        $this->assertEquals(100, $authLimit->maxAttempts);
        $this->assertEquals($user->id, $authLimit->key); // keyed by user ID when authenticated
    }

    public function test_api_rate_limiting_returns_429_when_limit_exceeded(): void
    {
        // ThrottleRequests hashes keys as md5($limiterName . $rawKey).
        // For a guest request with IP '10.0.0.1', the actual cache key is md5('api10.0.0.1').
        $ip = '10.0.0.1';
        $cacheKey = md5('api'.$ip);
        RateLimiter::clear($cacheKey);

        for ($i = 0; $i < 100; $i++) {
            RateLimiter::hit($cacheKey, 60);
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->getJson('/api/v1/products')
            ->assertStatus(429);

        RateLimiter::clear($cacheKey);
    }

    public function test_authenticated_user_does_not_exceed_rate_limit_normally(): void
    {
        $customer = $this->createCustomer();
        $cacheKey = md5('api'.$customer->id);
        RateLimiter::clear($cacheKey);

        // 5 requests — well below the real 100/min limit — must all succeed.
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($customer, 'sanctum')
                ->getJson('/api/v1/orders')
                ->assertStatus(200);
        }

        RateLimiter::clear($cacheKey);
    }

    public function test_authenticated_user_rate_limit_uses_user_id_as_key(): void
    {
        $customer = $this->createCustomer();

        // Authenticated requests are keyed by user ID (see bootstrap/app.php).
        // The middleware hashes it as md5('api' . $userId).
        $cacheKey = md5('api'.$customer->id);
        RateLimiter::clear($cacheKey);

        for ($i = 0; $i < 100; $i++) {
            RateLimiter::hit($cacheKey, 60);
        }

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/orders')
            ->assertStatus(429);

        RateLimiter::clear($cacheKey);
    }
}
