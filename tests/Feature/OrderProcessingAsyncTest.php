<?php

namespace Tests\Feature;

use App\Jobs\ProcessOrderPipeline;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderProcessingAsyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    }

    private function validAddress(): array
    {
        return [
            'street' => 'Rua das Flores, 123',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip_code' => '01000-000',
            'country' => 'BR',
        ];
    }

    public function test_order_creation_dispatches_async_processing_job(): void
    {
        Queue::fake();

        /** @var User $customer */
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $product = Product::factory()->create(['quantity' => 10, 'active' => true]);
        $cart = Cart::factory()->create(['user_id' => $customer->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $this->validAddress(),
                'billing_address' => $this->validAddress(),
                'payment_simulated' => true,
            ])
            ->assertCreated();

        Queue::assertPushed(ProcessOrderPipeline::class);

        $order = Order::query()->where('user_id', $customer->id)->latest('id')->firstOrFail();

        $this->assertSame('pending', $order->status);
        $this->assertSame(8, $product->fresh()->quantity);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
    }

    public function test_async_processing_failure_keeps_order_state_recoverable(): void
    {
        /** @var User $customer */
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $product = Product::factory()->create(['quantity' => 10, 'active' => true]);
        $cart = Cart::factory()->create(['user_id' => $customer->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $this->validAddress(),
                'billing_address' => $this->validAddress(),
                'payment_simulated' => true,
            ])
            ->assertCreated();

        $order = Order::query()->findOrFail($response->json('data.id'));

        $failingService = $this->createMock(OrderService::class);
        $failingService->method('processPendingOrder')->willThrowException(new RuntimeException('queue failure'));
        $this->app->instance(OrderService::class, $failingService);

        $this->expectException(RuntimeException::class);

        try {
            app()->call([new ProcessOrderPipeline($order->id), 'handle']);
        } finally {
            $this->assertSame('pending', $order->fresh()->status);
            $this->assertSame(8, $product->fresh()->quantity);
            $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
        }
    }
}
