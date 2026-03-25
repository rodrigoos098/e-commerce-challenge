<?php

namespace Tests\Unit\Models;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartItemTest extends TestCase
{
    use RefreshDatabase;

    // ── Fillable ──────────────────────────────────────────────────────────────

    public function test_fillable_attributes_are_correct(): void
    {
        $expected = ['cart_id', 'product_id', 'quantity'];

        $cartItem = new CartItem();

        $this->assertEquals($expected, $cartItem->getFillable());
    }

    // ── Casts ─────────────────────────────────────────────────────────────────

    public function test_quantity_is_cast_to_integer(): void
    {
        $cartItem = CartItem::factory()->create(['quantity' => '4']);

        $this->assertIsInt($cartItem->quantity);
        $this->assertEquals(4, $cartItem->quantity);
    }

    // ── Relacionamentos ───────────────────────────────────────────────────────

    public function test_cart_relationship_returns_belongs_to(): void
    {
        $cartItem = new CartItem();

        $this->assertInstanceOf(BelongsTo::class, $cartItem->cart());
    }

    public function test_cart_relationship_returns_cart_model(): void
    {
        $cartItem = CartItem::factory()->create();

        $this->assertInstanceOf(Cart::class, $cartItem->cart);
    }

    public function test_product_relationship_returns_belongs_to(): void
    {
        $cartItem = new CartItem();

        $this->assertInstanceOf(BelongsTo::class, $cartItem->product());
    }

    public function test_product_relationship_returns_product_model(): void
    {
        $cartItem = CartItem::factory()->create();

        $this->assertInstanceOf(Product::class, $cartItem->product);
    }

    // ── Integridade ───────────────────────────────────────────────────────────

    public function test_cart_item_belongs_to_correct_cart(): void
    {
        $cart = Cart::factory()->create();
        $cartItem = CartItem::factory()->create(['cart_id' => $cart->id]);

        $this->assertEquals($cart->id, $cartItem->cart->id);
    }

    public function test_cart_item_belongs_to_correct_product(): void
    {
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->create(['product_id' => $product->id]);

        $this->assertEquals($product->id, $cartItem->product->id);
    }
}
