<?php

namespace Tests\Unit\Models;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    // ── Fillable ──────────────────────────────────────────────────────────────

    public function test_fillable_attributes_are_correct(): void
    {
        $expected = ['user_id', 'session_id'];

        $cart = new Cart;

        $this->assertEquals($expected, $cart->getFillable());
    }

    // ── Relacionamentos ───────────────────────────────────────────────────────

    public function test_user_relationship_returns_belongs_to(): void
    {
        $cart = new Cart;

        $this->assertInstanceOf(BelongsTo::class, $cart->user());
    }

    public function test_user_relationship_returns_user_model(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $cart->user);
        $this->assertEquals($user->id, $cart->user->id);
    }

    public function test_items_relationship_returns_has_many(): void
    {
        $cart = new Cart;

        $this->assertInstanceOf(HasMany::class, $cart->items());
    }

    public function test_items_relationship_returns_cart_items(): void
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->count(2)->create(['cart_id' => $cart->id]);

        $this->assertCount(2, $cart->items);
        $this->assertInstanceOf(CartItem::class, $cart->items->first());
    }

    // ── Comportamento ─────────────────────────────────────────────────────────

    public function test_cart_can_be_created_without_user_as_guest(): void
    {
        $cart = Cart::factory()->create([
            'user_id' => null,
            'session_id' => 'abc123session',
        ]);

        $this->assertNull($cart->user_id);
        $this->assertEquals('abc123session', $cart->session_id);
    }

    public function test_cart_can_have_multiple_items(): void
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->count(5)->create(['cart_id' => $cart->id]);

        $this->assertCount(5, $cart->items);
    }
}
