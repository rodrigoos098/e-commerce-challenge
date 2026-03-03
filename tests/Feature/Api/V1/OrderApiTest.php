<?php

namespace Tests\Feature\Api\V1;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderApiTest extends TestCase
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

    private function validAddress(): array
    {
        return [
            'street' => '123 Main Street',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01234-567',
            'country' => 'BR',
        ];
    }

    private function setupCartWithProduct(User $user, ?Product $product = null): array
    {
        $product ??= Product::factory()->create(['quantity' => 10, 'active' => true, 'price' => 50.0]);
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        return [$cart, $product];
    }

    // ── Guest cannot access orders ────────────────────────────────────────────

    public function test_guest_cannot_list_orders(): void
    {
        $this->getJson('/api/v1/orders')->assertStatus(401);
    }

    public function test_guest_cannot_create_order(): void
    {
        $this->postJson('/api/v1/orders', [])->assertStatus(401);
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_customer_can_list_own_orders(): void
    {
        $customer = $this->createCustomer();
        Order::factory()->count(2)->create(['user_id' => $customer->id]);
        Order::factory()->create(); // from another user

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_list_all_orders(): void
    {
        $admin = $this->createAdmin();
        Order::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_filter_orders_by_status(): void
    {
        $admin = $this->createAdmin();
        Order::factory()->pending()->create();
        Order::factory()->delivered()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/orders?status=pending');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('pending', $response->json('data.0.status'));
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_customer_can_view_own_order(): void
    {
        $customer = $this->createCustomer();
        $order = Order::factory()->create(['user_id' => $customer->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_customer_cannot_view_other_users_order(): void
    {
        $customer = $this->createCustomer();
        $otherOrder = Order::factory()->create(); // belongs to another user

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/orders/{$otherOrder->id}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_view_any_order(): void
    {
        $admin = $this->createAdmin();
        $order = Order::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_create_order_from_cart(): void
    {
        $customer = $this->createCustomer();
        $this->setupCartWithProduct($customer);
        $address = $this->validAddress();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['id', 'status', 'total', 'items']]);

        $this->assertDatabaseHas('orders', ['user_id' => $customer->id, 'status' => 'pending']);
    }

    public function test_create_order_fails_with_empty_cart(): void
    {
        $customer = $this->createCustomer();
        $address = $this->validAddress();

        // No cart items

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_create_order_clears_cart(): void
    {
        $customer = $this->createCustomer();
        [$cart] = $this->setupCartWithProduct($customer);
        $address = $this->validAddress();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
            ]);

        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
    }

    public function test_create_order_fails_with_missing_address(): void
    {
        $customer = $this->createCustomer();
        $this->setupCartWithProduct($customer);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ── UpdateStatus (admin) ──────────────────────────────────────────────────

    public function test_admin_can_update_order_status(): void
    {
        $admin = $this->createAdmin();
        $order = Order::factory()->pending()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'shipped',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'shipped');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped']);
    }

    public function test_customer_cannot_update_order_status(): void
    {
        $customer = $this->createCustomer();
        $order = Order::factory()->pending()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'shipped',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_cannot_set_invalid_order_status(): void
    {
        $admin = $this->createAdmin();
        $order = Order::factory()->pending()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'invalid-status',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
