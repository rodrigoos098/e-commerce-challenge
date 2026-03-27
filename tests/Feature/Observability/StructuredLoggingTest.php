<?php

namespace Tests\Feature\Observability;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StructuredLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    }

    public function test_failed_login_is_logged_to_the_auth_channel(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        Log::shouldReceive('channel')
            ->once()
            ->with('auth')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Authentication failed'
                    && $context['email'] === 'user@example.com'
                    && array_key_exists('user_id', $context)
                    && array_key_exists('timestamp', $context);
            });

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_order_creation_is_logged_to_the_orders_channel(): void
    {
        Event::fake();
        Queue::fake();

        /** @var User $customer */
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $product = Product::factory()->create([
            'quantity' => 10,
            'active' => true,
            'price' => 50.0,
        ]);

        $cart = Cart::factory()->create(['user_id' => $customer->id]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $address = [
            'street' => '123 Main Street',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip_code' => '01234-567',
            'country' => 'BR',
        ];

        Log::shouldReceive('channel')
            ->once()
            ->with('stock')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($product): bool {
                return $message === 'Stock movement recorded'
                    && $context['product_id'] === $product->id
                    && $context['type'] === 'venda'
                    && $context['quantity'] === 2
                    && array_key_exists('timestamp', $context);
            });

        Log::shouldReceive('channel')
            ->once()
            ->with('orders')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($customer): bool {
                return $message === 'Order created'
                    && $context['user_id'] === $customer->id
                    && isset($context['order_id'])
                    && array_key_exists('timestamp', $context);
            });

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
                'payment_simulated' => true,
            ])
            ->assertStatus(201);
    }

    public function test_order_status_updates_are_logged_to_the_orders_channel(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $order = Order::factory()->create(['status' => 'processing']);

        Log::shouldReceive('channel')
            ->once()
            ->with('orders')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($admin, $order): bool {
                return $message === 'Order status updated'
                    && $context['user_id'] === $admin->id
                    && $context['order_id'] === $order->id
                    && $context['status'] === 'shipped'
                    && array_key_exists('timestamp', $context);
            });

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", [
                'status' => 'shipped',
            ])
            ->assertStatus(200);
    }

    public function test_stock_movements_are_logged_to_the_stock_channel(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $product = Product::factory()->create([
            'quantity' => 5,
            'min_quantity' => 2,
        ]);

        Log::shouldReceive('channel')
            ->once()
            ->with('stock')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($admin, $product): bool {
                return $message === 'Stock movement recorded'
                    && $context['user_id'] === $admin->id
                    && $context['product_id'] === $product->id
                    && $context['type'] === 'entrada'
                    && $context['quantity'] === 3
                    && array_key_exists('timestamp', $context);
            });

        $this->actingAs($admin, 'sanctum');

        app(StockService::class)->increaseStock($product->id, 3, 'Manual restock');
    }
}
