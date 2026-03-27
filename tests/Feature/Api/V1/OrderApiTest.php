<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\SendOrderConfirmationEmail;
use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This action is unauthorized.');
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
        Queue::fake();

        $customer = $this->createCustomer();
        [, $product] = $this->setupCartWithProduct($customer);
        $address = $this->validAddress();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
                'payment_simulated' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Pagamento simulado com sucesso e pedido criado.')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.payment_method', Order::MOCK_PAYMENT_METHOD)
            ->assertJsonStructure(['success', 'data' => ['id', 'status', 'total', 'items']]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'paid',
            'payment_method' => Order::MOCK_PAYMENT_METHOD,
        ]);
        $orderId = $response->json('data.id');
        $this->assertSame(8, $product->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reference_type' => 'order',
            'reference_id' => $orderId,
            'type' => 'venda',
            'quantity' => 2,
        ]);

        Queue::assertPushed(SendOrderConfirmationEmail::class, function (SendOrderConfirmationEmail $job) use ($orderId): bool {
            return $job->orderId === $orderId
                && $job->notificationType === OrderConfirmationMail::TYPE_CREATED;
        });

        Queue::assertPushed(SendOrderConfirmationEmail::class, function (SendOrderConfirmationEmail $job) use ($orderId): bool {
            return $job->orderId === $orderId
                && $job->notificationType === OrderConfirmationMail::TYPE_PAYMENT_CONFIRMED;
        });
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
                'payment_simulated' => true,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'errors']);
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
                'payment_simulated' => true,
            ]);

        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
    }

    public function test_retrying_order_submission_does_not_create_a_duplicate_order(): void
    {
        $customer = $this->createCustomer();
        [, $product] = $this->setupCartWithProduct($customer);
        $address = $this->validAddress();

        $firstResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
                'payment_simulated' => true,
            ]);

        $firstResponse->assertCreated();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
                'payment_simulated' => true,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['cart']]);

        $this->assertSame(1, Order::query()->where('user_id', $customer->id)->count());
        $this->assertSame(8, $product->fresh()->quantity);
        $this->assertSame(1, StockMovement::query()->where('product_id', $product->id)->where('type', 'venda')->count());
    }

    public function test_second_order_is_rejected_after_stock_is_consumed(): void
    {
        $firstCustomer = $this->createCustomer();
        $secondCustomer = $this->createCustomer();
        $product = Product::factory()->create(['quantity' => 2, 'active' => true, 'price' => 50.0]);
        $address = $this->validAddress();

        $this->setupCartWithProduct($firstCustomer, $product);
        $this->setupCartWithProduct($secondCustomer, $product);

        $this->actingAs($firstCustomer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
                'payment_simulated' => true,
            ])
            ->assertStatus(201);

        $this->actingAs($secondCustomer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
                'payment_simulated' => true,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_create_order_fails_with_missing_address(): void
    {
        $customer = $this->createCustomer();
        $this->setupCartWithProduct($customer);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'errors']);
    }

    public function test_create_order_requires_payment_simulation(): void
    {
        $customer = $this->createCustomer();
        $this->setupCartWithProduct($customer);
        $address = $this->validAddress();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['payment_simulated']);
    }

    // ── UpdateStatus (admin) ──────────────────────────────────────────────────

    public function test_admin_can_update_order_status(): void
    {
        $admin = $this->createAdmin();
        $order = Order::factory()->pending()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'processing',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'processing');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
    }

    public function test_admin_can_apply_full_valid_status_chain(): void
    {
        Queue::fake();

        $admin = $this->createAdmin();
        $order = Order::factory()->pending()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'processing'])
            ->assertOk()
            ->assertJsonPath('data.status', 'processing');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertOk()
            ->assertJsonPath('data.status', 'shipped');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertOk()
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

    public function test_admin_cannot_skip_steps_in_status_chain(): void
    {
        $admin = $this->createAdmin();
        $order = Order::factory()->pending()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['status']]);
    }

    public function test_admin_cannot_cancel_delivered_order(): void
    {
        $admin = $this->createAdmin();
        $order = Order::factory()->delivered()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'cancelled'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['status']]);
    }

    public function test_cancelling_order_restores_stock_and_records_return_movement(): void
    {
        Queue::fake();

        $admin = $this->createAdmin();
        $customer = $this->createCustomer();
        [, $product] = $this->setupCartWithProduct($customer, Product::factory()->create([
            'quantity' => 10,
            'active' => true,
            'price' => 50.0,
        ]));
        $address = $this->validAddress();

        $createResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
                'payment_simulated' => true,
            ]);

        $orderId = $createResponse->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$orderId}/status", [
                'status' => 'cancelled',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $product->refresh();

        $this->assertSame(10, $product->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'devolucao',
            'quantity' => 2,
            'reference_type' => 'order',
            'reference_id' => $orderId,
        ]);

        $movement = StockMovement::query()
            ->where('product_id', $product->id)
            ->where('type', 'devolucao')
            ->where('reference_type', 'order')
            ->where('reference_id', $orderId)
            ->firstOrFail();

        $this->assertInstanceOf(Order::class, $movement->reference);
        $this->assertSame($orderId, $movement->reference->id);

        Queue::assertPushed(SendOrderConfirmationEmail::class, function (SendOrderConfirmationEmail $job) use ($orderId): bool {
            return $job->orderId === $orderId
                && $job->notificationType === OrderConfirmationMail::TYPE_CANCELLED;
        });
    }

    public function test_repeating_same_admin_status_does_not_queue_duplicate_notification(): void
    {
        Queue::fake();

        $admin = $this->createAdmin();
        $order = Order::factory()->create(['status' => 'shipped']);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertOk()
            ->assertJsonPath('data.status', 'shipped');

        Queue::assertNotPushed(SendOrderConfirmationEmail::class);
    }

    public function test_cancelling_order_with_legacy_sale_reference_restores_stock_and_records_return_movement(): void
    {
        $admin = $this->createAdmin();
        $customer = $this->createCustomer();
        $product = Product::factory()->create([
            'quantity' => 7,
            'active' => true,
            'price' => 50.0,
        ]);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'processing',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 50.0,
            'total_price' => 150.0,
        ]);

        StockMovement::factory()->create([
            'product_id' => $product->id,
            'type' => 'venda',
            'quantity' => 3,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'cancelled',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $product->refresh();

        $this->assertSame(10, $product->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'devolucao',
            'quantity' => 3,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        $movement = StockMovement::query()
            ->where('product_id', $product->id)
            ->where('type', 'devolucao')
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->firstOrFail();

        $this->assertInstanceOf(Order::class, $movement->reference);
        $this->assertSame($order->id, $movement->reference->id);
    }

    public function test_admin_cannot_apply_invalid_status_transition(): void
    {
        $admin = $this->createAdmin();
        $order = Order::factory()->create(['status' => 'shipped']);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'cancelled',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['status']]);
    }

    public function test_customer_can_cancel_own_eligible_order(): void
    {
        $customer = $this->createCustomer();
        $product = Product::factory()->create(['quantity' => 10, 'active' => true, 'price' => 50.0]);
        $address = $this->validAddress();

        $this->setupCartWithProduct($customer, $product);

        $createResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
                'payment_simulated' => true,
            ]);

        $orderId = $createResponse->json('data.id');

        $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/orders/{$orderId}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.can_cancel', false);
    }

    public function test_customer_cannot_cancel_shipped_order(): void
    {
        $customer = $this->createCustomer();
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'shipped',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/cancel")
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    public function test_customer_cannot_update_order_status(): void
    {
        $customer = $this->createCustomer();
        $order = Order::factory()->pending()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'shipped',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This action is unauthorized.');
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
