<?php

namespace Tests\Feature;

use App\Events\StockLow;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
    }

    private function validAddress(): array
    {
        return [
            'street' => 'Av. Paulista, 1000',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01310-100',
            'country' => 'BR',
        ];
    }

    public function test_stock_decreases_after_order_is_created(): void
    {
        $product = Product::factory()->create(['price' => 50.0, 'quantity' => 10, 'active' => true]);
        $address = $this->validAddress();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 3]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address]);

        $product->refresh();
        $this->assertEquals(7, $product->quantity);
    }

    public function test_stock_movement_is_recorded_after_order(): void
    {
        $product = Product::factory()->create(['price' => 50.0, 'quantity' => 10, 'active' => true]);
        $address = $this->validAddress();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 3]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address]);

        $order = Order::where('user_id', $this->customer->id)->first();

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'venda',
            'quantity' => 3,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);
    }

    public function test_stock_low_event_is_fired_when_stock_drops_below_minimum(): void
    {
        Event::fake([StockLow::class]);

        $product = Product::factory()->create([
            'price' => 50.0,
            'quantity' => 5,
            'min_quantity' => 5, // after ordering 2, qty will be 3 which is <= min
            'active' => true,
        ]);
        $address = $this->validAddress();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address]);

        Event::assertDispatched(StockLow::class, function (StockLow $event) use ($product) {
            return $event->product->id === $product->id;
        });
    }

    public function test_stock_low_event_not_fired_when_stock_remains_above_minimum(): void
    {
        Event::fake([StockLow::class]);

        $product = Product::factory()->create([
            'price' => 50.0,
            'quantity' => 20,
            'min_quantity' => 5,
            'active' => true,
        ]);
        $address = $this->validAddress();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address]);

        Event::assertNotDispatched(StockLow::class);
    }

    public function test_order_with_multiple_products_decreases_all_stocks(): void
    {
        $product1 = Product::factory()->create(['price' => 50.0, 'quantity' => 10, 'active' => true]);
        $product2 = Product::factory()->create(['price' => 30.0, 'quantity' => 8, 'active' => true]);
        $address = $this->validAddress();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product1->id, 'quantity' => 3]);
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product2->id, 'quantity' => 2]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address]);

        $product1->refresh();
        $product2->refresh();

        $this->assertEquals(7, $product1->quantity);
        $this->assertEquals(6, $product2->quantity);
    }

    public function test_each_order_item_creates_its_own_stock_movement(): void
    {
        $product1 = Product::factory()->create(['price' => 50.0, 'quantity' => 10, 'active' => true]);
        $product2 = Product::factory()->create(['price' => 30.0, 'quantity' => 8, 'active' => true]);
        $address = $this->validAddress();

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product1->id, 'quantity' => 3]);
        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product2->id, 'quantity' => 2]);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/orders', ['shipping_address' => $address, 'billing_address' => $address]);

        $order = Order::where('user_id', $this->customer->id)->first();
        $movements = StockMovement::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->get();

        $this->assertCount(2, $movements);
        $this->assertTrue($movements->contains('product_id', $product1->id));
        $this->assertTrue($movements->contains('product_id', $product2->id));
    }
}
