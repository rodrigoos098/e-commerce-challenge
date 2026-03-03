<?php

namespace Tests\Unit\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(
        ?CartRepositoryInterface $cartRepo = null,
        ?ProductRepositoryInterface $productRepo = null,
    ): CartService {
        $cartRepo ??= Mockery::mock(CartRepositoryInterface::class);
        $productRepo ??= Mockery::mock(ProductRepositoryInterface::class);

        return new CartService($cartRepo, $productRepo);
    }

    // ── GetOrCreateForUser ────────────────────────────────────────────────────

    public function test_get_or_create_for_user_delegates_to_repository(): void
    {
        $cart = Cart::factory()->make(['id' => 1]);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findOrCreateForUser')->once()->with(42)->andReturn($cart);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);

        $result = $this->makeService($cartRepo, $productRepo)->getOrCreateForUser(42);

        $this->assertSame($cart, $result);
    }

    // ── AddItem ───────────────────────────────────────────────────────────────

    public function test_add_item_throws_when_product_not_found(): void
    {
        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(99)->andReturn(null);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);

        $this->expectException(ValidationException::class);

        $this->makeService($cartRepo, $productRepo)->addItem(1, 99, 1);
    }

    public function test_add_item_throws_when_product_is_inactive(): void
    {
        $product = Product::factory()->make(['id' => 5, 'active' => false, 'quantity' => 10]);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(5)->andReturn($product);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);

        $this->expectException(ValidationException::class);

        $this->makeService($cartRepo, $productRepo)->addItem(1, 5, 1);
    }

    public function test_add_item_throws_when_insufficient_stock(): void
    {
        $product = Product::factory()->make(['id' => 5, 'active' => true, 'quantity' => 3]);
        $cart = Cart::factory()->make(['id' => 1]);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->andReturn($product);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findOrCreateForUser')->andReturn($cart);
        $cartRepo->shouldReceive('findItemByCartAndProduct')->andReturn(null);

        $this->expectException(ValidationException::class);

        $this->makeService($cartRepo, $productRepo)->addItem(1, 5, 10); // wants 10, only 3 available
    }

    public function test_add_item_succeeds_when_stock_is_sufficient(): void
    {
        $product = Product::factory()->make(['id' => 5, 'active' => true, 'quantity' => 20]);
        $cart = Cart::factory()->make(['id' => 1]);
        $cartItem = CartItem::factory()->make(['id' => 1, 'quantity' => 2]);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->andReturn($product);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findOrCreateForUser')->andReturn($cart);
        $cartRepo->shouldReceive('findItemByCartAndProduct')->andReturn(null);
        $cartRepo->shouldReceive('addItem')->once()->with($cart, 5, 2)->andReturn($cartItem);

        $result = $this->makeService($cartRepo, $productRepo)->addItem(1, 5, 2);

        $this->assertSame($cartItem, $result);
    }

    public function test_add_item_considers_existing_cart_item_quantity(): void
    {
        $product = Product::factory()->make(['id' => 5, 'active' => true, 'quantity' => 5]);
        $cart = Cart::factory()->make(['id' => 1]);
        $existingItem = CartItem::factory()->make(['quantity' => 4]); // already 4 in cart

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->andReturn($product);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findOrCreateForUser')->andReturn($cart);
        $cartRepo->shouldReceive('findItemByCartAndProduct')->andReturn($existingItem);

        // 4 (existing) + 2 (new) = 6, but only 5 in stock → should throw
        $this->expectException(ValidationException::class);

        $this->makeService($cartRepo, $productRepo)->addItem(1, 5, 2);
    }

    // ── UpdateItem ────────────────────────────────────────────────────────────

    public function test_update_item_throws_when_insufficient_stock(): void
    {
        $product = Product::factory()->make(['id' => 5, 'active' => true, 'quantity' => 3]);
        $cartItem = CartItem::factory()->make(['product_id' => 5, 'quantity' => 1]);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->andReturn($product);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);

        $this->expectException(ValidationException::class);

        $this->makeService($cartRepo, $productRepo)->updateItem($cartItem, 10); // wants 10, only 3 available
    }

    public function test_update_item_succeeds_when_stock_sufficient(): void
    {
        $product = Product::factory()->make(['id' => 5, 'active' => true, 'quantity' => 10]);
        $cartItem = CartItem::factory()->make(['product_id' => 5, 'quantity' => 1]);
        $updatedItem = CartItem::factory()->make(['quantity' => 5]);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->andReturn($product);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('updateItem')->once()->with($cartItem, 5)->andReturn($updatedItem);

        $result = $this->makeService($cartRepo, $productRepo)->updateItem($cartItem, 5);

        $this->assertSame($updatedItem, $result);
    }

    // ── RemoveItem ────────────────────────────────────────────────────────────

    public function test_remove_item_delegates_to_repository(): void
    {
        $cartItem = CartItem::factory()->make(['id' => 1]);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('removeItem')->once()->with($cartItem)->andReturn(true);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);

        $result = $this->makeService($cartRepo, $productRepo)->removeItem($cartItem);

        $this->assertTrue($result);
    }

    // ── Clear ─────────────────────────────────────────────────────────────────

    public function test_clear_returns_true_when_no_cart_exists(): void
    {
        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn(null);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);

        $result = $this->makeService($cartRepo, $productRepo)->clear(99);

        $this->assertTrue($result);
    }

    public function test_clear_clears_cart_when_it_exists(): void
    {
        $cart = Cart::factory()->make(['id' => 1]);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($cart);
        $cartRepo->shouldReceive('clear')->once()->with($cart)->andReturn(true);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);

        $result = $this->makeService($cartRepo, $productRepo)->clear(1);

        $this->assertTrue($result);
    }
}
