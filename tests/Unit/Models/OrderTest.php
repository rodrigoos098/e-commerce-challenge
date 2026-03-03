<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    // ── Fillable ──────────────────────────────────────────────────────────────

    public function test_fillable_attributes_are_correct(): void
    {
        $expected = [
            'user_id', 'status', 'total', 'subtotal', 'tax',
            'shipping_cost', 'shipping_address', 'billing_address', 'notes',
        ];

        $order = new Order;

        $this->assertEquals($expected, $order->getFillable());
    }

    // ── Casts ─────────────────────────────────────────────────────────────────

    public function test_total_is_cast_to_decimal(): void
    {
        $order = Order::factory()->create(['total' => '100.5']);

        $this->assertIsString($order->total);
        $this->assertEquals('100.50', $order->total);
    }

    public function test_subtotal_is_cast_to_decimal(): void
    {
        $order = Order::factory()->create(['subtotal' => '80.0']);

        $this->assertIsString($order->subtotal);
        $this->assertEquals('80.00', $order->subtotal);
    }

    public function test_tax_is_cast_to_decimal(): void
    {
        $order = Order::factory()->create(['tax' => '8.0']);

        $this->assertIsString($order->tax);
        $this->assertEquals('8.00', $order->tax);
    }

    public function test_shipping_cost_is_cast_to_decimal(): void
    {
        $order = Order::factory()->create(['shipping_cost' => '12.5']);

        $this->assertIsString($order->shipping_cost);
        $this->assertEquals('12.50', $order->shipping_cost);
    }

    public function test_shipping_address_is_cast_to_array(): void
    {
        $address = ['street' => 'Rua A', 'city' => 'São Paulo', 'state' => 'SP'];
        $order = Order::factory()->create(['shipping_address' => $address]);

        $this->assertIsArray($order->shipping_address);
        $this->assertEquals('Rua A', $order->shipping_address['street']);
    }

    public function test_billing_address_is_cast_to_array(): void
    {
        $address = ['street' => 'Rua B', 'city' => 'Rio', 'state' => 'RJ'];
        $order = Order::factory()->create(['billing_address' => $address]);

        $this->assertIsArray($order->billing_address);
        $this->assertEquals('Rua B', $order->billing_address['street']);
    }

    // ── Status constants ──────────────────────────────────────────────────────

    public function test_statuses_constant_contains_expected_values(): void
    {
        $expected = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        $this->assertEquals($expected, Order::STATUSES);
    }

    public function test_order_can_be_created_with_pending_status(): void
    {
        $order = Order::factory()->pending()->create();

        $this->assertEquals('pending', $order->status);
    }

    public function test_order_can_be_created_with_delivered_status(): void
    {
        $order = Order::factory()->delivered()->create();

        $this->assertEquals('delivered', $order->status);
    }

    // ── Relacionamentos ───────────────────────────────────────────────────────

    public function test_user_relationship_returns_belongs_to(): void
    {
        $order = new Order;

        $this->assertInstanceOf(BelongsTo::class, $order->user());
    }

    public function test_user_relationship_returns_user_model(): void
    {
        $order = Order::factory()->create();

        $this->assertInstanceOf(User::class, $order->user);
    }

    public function test_items_relationship_returns_has_many(): void
    {
        $order = new Order;

        $this->assertInstanceOf(HasMany::class, $order->items());
    }

    public function test_items_relationship_returns_order_items(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->count(3)->create(['order_id' => $order->id]);

        $this->assertCount(3, $order->items);
        $this->assertInstanceOf(OrderItem::class, $order->items->first());
    }
}
