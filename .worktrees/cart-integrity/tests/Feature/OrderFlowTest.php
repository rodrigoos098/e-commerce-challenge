<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
    }

    private function validAddress(): array
    {
        return [
            'street' => 'Rua das Flores, 123',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01310-100',
            'country' => 'BR',
        ];
    }

    public function test_complete_order_flow_from_cart_to_confirmation(): void
    {
        $product = Product::factory()->create([
            'price' => 100.0,
            'quantity' => 10,
            'active' => true,
        ]);
        $address = $this->validAddress();

        // 1. Add item to cart
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2]);

        // 2. Create order from cart
        $orderResponse = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
            ]);

        $orderResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        // 3. Verify order exists in database with correct totals (qty=2, price=100)
        $order = Order::where('user_id', $this->customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(200.0, $order->subtotal);
        $this->assertEquals(20.0, $order->tax);
        $this->assertEquals(220.0, $order->total);

        // 4. Verify cart is cleared
        $cart = Cart::where('user_id', $this->customer->id)->first();
        $this->assertTrue(
            $cart === null || $cart->items()->count() === 0,
        );
    }

    public function test_order_contains_correct_items(): void
    {
        $product1 = Product::factory()->create(['price' => 50.0, 'quantity' => 10, 'active' => true]);
        $product2 = Product::factory()->create(['price' => 75.0, 'quantity' => 10, 'active' => true]);
        $address = $this->validAddress();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product1->id, 'quantity' => 1]);
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product2->id, 'quantity' => 2]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
            ]);

        $order = Order::where('user_id', $this->customer->id)->first();
        $this->assertCount(2, $order->items);
    }

    public function test_customer_can_list_their_orders_after_creation(): void
    {
        $product = Product::factory()->create(['price' => 50.0, 'quantity' => 5, 'active' => true]);
        $address = $this->validAddress();

        // Create 2 orders
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address]);

        // Reset stock for second order
        $product->update(['quantity' => 5]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address]);

        // List orders
        $listResponse = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/v1/orders');

        $listResponse->assertStatus(200);
        $this->assertEquals(2, $listResponse->json('meta.total'));
    }

    public function test_admin_can_update_order_status_in_flow(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $product = Product::factory()->create(['price' => 30.0, 'quantity' => 5, 'active' => true]);
        $address = $this->validAddress();

        // Customer creates order
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address]);

        $order = Order::where('user_id', $this->customer->id)->first();

        // Admin updates status
        $statusResponse = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'processing']);

        $statusResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'processing');
    }

    public function test_customer_can_view_order_details_after_creation(): void
    {
        $product = Product::factory()->create(['price' => 99.0, 'quantity' => 5, 'active' => true]);
        $address = $this->validAddress();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1]);

        $createResponse = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address]);

        $orderId = $createResponse->json('data.id');

        $showResponse = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/v1/orders/{$orderId}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $orderId)
            ->assertJsonStructure(['data' => ['id', 'status', 'total', 'items', 'shipping_address']]);
    }
}
