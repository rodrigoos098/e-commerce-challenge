<?php

namespace Tests\Feature;

use App\Jobs\ProcessOrderPipeline;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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
            ])
            ->assertCreated();

        Queue::assertPushed(ProcessOrderPipeline::class);
    }
}
