<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationEmail;
use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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
        Queue::fake();

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
                'payment_simulated' => true,
            ]);

        $orderResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.payment_method', Order::MOCK_PAYMENT_METHOD);

        // 3. Verify order exists in database with correct totals (qty=2, price=100)
        $order = Order::where('user_id', $this->customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(200.0, $order->subtotal);
        $this->assertEquals(20.0, $order->tax);
        $this->assertEquals(0.0, (float) $order->shipping_cost);
        $this->assertEquals(220.0, $order->total);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(Order::MOCK_PAYMENT_METHOD, $order->payment_method);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(8, $product->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'type' => 'venda',
            'quantity' => 2,
        ]);

        // 4. Verify cart is cleared
        $cart = Cart::where('user_id', $this->customer->id)->first();
        $this->assertTrue(
            $cart === null || $cart->items()->count() === 0,
        );

        Queue::assertPushed(SendOrderConfirmationEmail::class, function (SendOrderConfirmationEmail $job) use ($order): bool {
            return $job->orderId === $order->id
                && $job->notificationType === OrderConfirmationMail::TYPE_CREATED;
        });

        Queue::assertPushed(SendOrderConfirmationEmail::class, function (SendOrderConfirmationEmail $job) use ($order): bool {
            return $job->orderId === $order->id
                && $job->notificationType === OrderConfirmationMail::TYPE_PAYMENT_CONFIRMED;
        });
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
                'payment_simulated' => true,
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
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address, 'payment_simulated' => true]);

        // Reset stock for second order
        $product->update(['quantity' => 5]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address, 'payment_simulated' => true]);

        // List orders
        $listResponse = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/v1/orders');

        $listResponse->assertStatus(200);
        $this->assertEquals(2, $listResponse->json('meta.total'));
    }

    public function test_admin_can_update_order_status_in_flow(): void
    {
        Queue::fake();

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $product = Product::factory()->create(['price' => 30.0, 'quantity' => 5, 'active' => true]);
        $address = $this->validAddress();

        // Customer creates order
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address, 'payment_simulated' => true]);

        $order = Order::where('user_id', $this->customer->id)->firstOrFail();

        // Admin updates status
        $statusResponse = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'processing']);

        $statusResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'processing');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'shipped');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'delivered');

        Queue::assertPushed(SendOrderConfirmationEmail::class, function (SendOrderConfirmationEmail $job) use ($order): bool {
            return $job->orderId === $order->id
                && $job->notificationType === OrderConfirmationMail::TYPE_SHIPPED;
        });

        Queue::assertPushed(SendOrderConfirmationEmail::class, function (SendOrderConfirmationEmail $job) use ($order): bool {
            return $job->orderId === $order->id
                && $job->notificationType === OrderConfirmationMail::TYPE_DELIVERED;
        });
    }

    public function test_web_checkout_creates_single_consistent_order_and_clears_cart_after_stock_commit(): void
    {
        $product = Product::factory()->create([
            'price' => 80.0,
            'quantity' => 4,
            'active' => true,
        ]);

        $this->actingAs($this->customer)
            ->post('/cart/items', ['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($this->customer)
            ->post('/customer/orders', [
                'shipping_mode' => 'new',
                'shipping_name' => 'Cliente Teste',
                'shipping_street' => 'Rua Alfa, 10',
                'shipping_city' => 'Sao Paulo',
                'shipping_state' => 'SP',
                'shipping_zip' => '01000-000',
                'shipping_country' => 'BR',
                'same_billing' => true,
                'notes' => 'Entregar no periodo da tarde.',
                'payment_simulated' => true,
            ])
            ->assertRedirect();

        $order = Order::where('user_id', $this->customer->id)->latest('id')->firstOrFail();

        $this->assertSame(Order::INITIAL_STATUS, $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(Order::MOCK_PAYMENT_METHOD, $order->payment_method);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(2, StockMovement::query()->where('reference_id', $order->id)->sum('quantity'));
        $this->assertSame(2, $product->fresh()->quantity);

        $cart = Cart::where('user_id', $this->customer->id)->first();
        $this->assertTrue($cart === null || $cart->items()->count() === 0);
    }

    public function test_customer_can_view_order_details_after_creation(): void
    {
        $product = Product::factory()->create(['price' => 99.0, 'quantity' => 5, 'active' => true]);
        $address = $this->validAddress();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1]);

        $createResponse = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address, 'payment_simulated' => true]);

        $orderId = $createResponse->json('data.id');

        $showResponse = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/v1/orders/{$orderId}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $orderId)
            ->assertJsonStructure(['data' => ['id', 'status', 'total', 'items', 'shipping_address']]);
    }

    public function test_order_totalization_applies_shipping_rule_for_subtotal_below_free_shipping_threshold(): void
    {
        $product = Product::factory()->create([
            'price' => 80.0,
            'quantity' => 5,
            'active' => true,
        ]);
        $address = $this->validAddress();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
                'payment_simulated' => true,
            ])
            ->assertStatus(201);

        $order = Order::where('user_id', $this->customer->id)->firstOrFail();

        $this->assertEquals(80.0, $order->subtotal);
        $this->assertEquals(8.0, $order->tax);
        $this->assertEquals(14.9, (float) $order->shipping_cost);
        $this->assertEquals(102.9, (float) $order->total);
        $this->assertSame('paid', $order->payment_status);
    }

    public function test_api_order_creation_requires_payment_simulation(): void
    {
        $product = Product::factory()->create([
            'price' => 80.0,
            'quantity' => 5,
            'active' => true,
        ]);
        $address = $this->validAddress();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_simulated']);
    }
}
