<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemTest extends TestCase
{
    use RefreshDatabase;

    // ── Fillable ──────────────────────────────────────────────────────────────

    public function test_fillable_attributes_are_correct(): void
    {
        $expected = ['order_id', 'product_id', 'quantity', 'unit_price', 'total_price'];

        $orderItem = new OrderItem;

        $this->assertEquals($expected, $orderItem->getFillable());
    }

    // ── Casts ─────────────────────────────────────────────────────────────────

    public function test_quantity_is_cast_to_integer(): void
    {
        $orderItem = OrderItem::factory()->create(['quantity' => '3']);

        $this->assertIsInt($orderItem->quantity);
        $this->assertEquals(3, $orderItem->quantity);
    }

    public function test_unit_price_is_cast_to_decimal(): void
    {
        $orderItem = OrderItem::factory()->create(['unit_price' => '29.9']);

        $this->assertIsString($orderItem->unit_price);
        $this->assertEquals('29.90', $orderItem->unit_price);
    }

    public function test_total_price_is_cast_to_decimal(): void
    {
        $orderItem = OrderItem::factory()->create(['total_price' => '89.7']);

        $this->assertIsString($orderItem->total_price);
        $this->assertEquals('89.70', $orderItem->total_price);
    }

    // ── Relacionamentos ───────────────────────────────────────────────────────

    public function test_order_relationship_returns_belongs_to(): void
    {
        $orderItem = new OrderItem;

        $this->assertInstanceOf(BelongsTo::class, $orderItem->order());
    }

    public function test_order_relationship_returns_order_model(): void
    {
        $orderItem = OrderItem::factory()->create();

        $this->assertInstanceOf(Order::class, $orderItem->order);
    }

    public function test_product_relationship_returns_belongs_to(): void
    {
        $orderItem = new OrderItem;

        $this->assertInstanceOf(BelongsTo::class, $orderItem->product());
    }

    public function test_product_relationship_returns_product_model(): void
    {
        $orderItem = OrderItem::factory()->create();

        $this->assertInstanceOf(Product::class, $orderItem->product);
    }

    // ── Integridade ───────────────────────────────────────────────────────────

    public function test_order_item_belongs_to_correct_order(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $this->assertEquals($order->id, $orderItem->order->id);
    }

    public function test_order_item_belongs_to_correct_product(): void
    {
        $product = Product::factory()->create();
        $orderItem = OrderItem::factory()->create(['product_id' => $product->id]);

        $this->assertEquals($product->id, $orderItem->product->id);
    }
}
