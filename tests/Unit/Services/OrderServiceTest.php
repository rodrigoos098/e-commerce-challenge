<?php

namespace Tests\Unit\Services;

use App\DTOs\OrderDTO;
use App\Events\OrderCreated;
use App\Jobs\ProcessOrderJob;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(
        ?OrderRepositoryInterface $orderRepo = null,
        ?CartRepositoryInterface $cartRepo = null,
        ?ProductRepositoryInterface $productRepo = null,
    ): OrderService {
        $orderRepo ??= Mockery::mock(OrderRepositoryInterface::class);
        $cartRepo ??= Mockery::mock(CartRepositoryInterface::class);
        $productRepo ??= Mockery::mock(ProductRepositoryInterface::class);

        return new OrderService($orderRepo, $cartRepo, $productRepo);
    }

    // ── CreateFromCart — Validations ───────────────────────────────────────────

    public function test_create_from_cart_throws_when_no_cart_found(): void
    {
        $dto = new OrderDTO(
            userId: 1,
            shippingAddress: ['street' => '1 Main', 'city' => 'SP', 'state' => 'SP', 'zip_code' => '01234', 'country' => 'BR'],
            billingAddress: ['street' => '1 Main', 'city' => 'SP', 'state' => 'SP', 'zip_code' => '01234', 'country' => 'BR'],
        );

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn(null);

        $this->expectException(ValidationException::class);

        $this->makeService(cartRepo: $cartRepo)->createFromCart($dto);
    }

    public function test_create_from_cart_throws_when_cart_is_empty(): void
    {
        $cart = Cart::factory()->make(['id' => 1]);
        $cart->setRelation('items', collect());

        $dto = new OrderDTO(
            userId: 1,
            shippingAddress: ['street' => '1 Main', 'city' => 'SP', 'state' => 'SP', 'zip_code' => '01234', 'country' => 'BR'],
            billingAddress: ['street' => '1 Main', 'city' => 'SP', 'state' => 'SP', 'zip_code' => '01234', 'country' => 'BR'],
        );

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($cart);

        $this->expectException(ValidationException::class);

        $this->makeService(cartRepo: $cartRepo)->createFromCart($dto);
    }

    public function test_create_from_cart_throws_when_product_inactive(): void
    {
        $product = Product::factory()->make(['id' => 5, 'active' => false, 'quantity' => 10, 'price' => 50.0]);
        $cartItem = CartItem::factory()->make(['product_id' => 5, 'quantity' => 1]);
        $cartItem->setRelation('product', $product);

        $cart = Cart::factory()->make(['id' => 1]);
        $cart->setRelation('items', collect([$cartItem]));

        $dto = new OrderDTO(userId: 1, shippingAddress: [], billingAddress: []);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($cart);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(5)->andReturn($product);

        $this->expectException(ValidationException::class);

        $this->makeService(cartRepo: $cartRepo, productRepo: $productRepo)->createFromCart($dto);
    }

    public function test_create_from_cart_throws_when_insufficient_stock(): void
    {
        $product = Product::factory()->make(['id' => 5, 'active' => true, 'quantity' => 1, 'price' => 50.0, 'name' => 'Produto X']);
        $cartItem = CartItem::factory()->make(['product_id' => 5, 'quantity' => 5]);
        $cartItem->setRelation('product', $product);

        $cart = Cart::factory()->make(['id' => 1]);
        $cart->setRelation('items', collect([$cartItem]));

        $dto = new OrderDTO(userId: 1, shippingAddress: [], billingAddress: []);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($cart);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(5)->andReturn($product);

        $this->expectException(ValidationException::class);

        $this->makeService(cartRepo: $cartRepo, productRepo: $productRepo)->createFromCart($dto);
    }

    // ── CreateFromCart — Success ───────────────────────────────────────────────

    public function test_create_from_cart_fires_order_created_event(): void
    {
        Event::fake();
        Bus::fake();

        $product = Product::factory()->make([
            'id' => 5, 'active' => true, 'quantity' => 10,
            'price' => 50.0, 'min_quantity' => 2,
        ]);

        $cartItem = CartItem::factory()->make(['product_id' => 5, 'quantity' => 2]);
        $cartItem->setRelation('product', $product);

        $cart = Cart::factory()->make(['id' => 1]);
        $cart->setRelation('items', collect([$cartItem]));

        $order = Order::factory()->make(['id' => 100]);
        $order->setRelation('items', collect([$cartItem]));

        $dto = new OrderDTO(
            userId: 1,
            shippingAddress: ['street' => '1St', 'city' => 'SP', 'state' => 'SP', 'zip_code' => '01234', 'country' => 'BR'],
            billingAddress: ['street' => '1St', 'city' => 'SP', 'state' => 'SP', 'zip_code' => '01234', 'country' => 'BR'],
        );

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($cart);
        $cartRepo->shouldReceive('clear')->andReturn(true);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->andReturn($product);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('create')->andReturn($order);

        $this->makeService($orderRepo, $cartRepo, $productRepo)->createFromCart($dto);

        Event::assertDispatched(OrderCreated::class);
    }

    public function test_create_from_cart_dispatches_process_order_job(): void
    {
        Event::fake();
        Bus::fake();

        $product = Product::factory()->make([
            'id' => 5, 'active' => true, 'quantity' => 10, 'price' => 50.0, 'min_quantity' => 2,
        ]);

        $cartItem = CartItem::factory()->make(['product_id' => 5, 'quantity' => 1]);
        $cartItem->setRelation('product', $product);

        $cart = Cart::factory()->make(['id' => 1]);
        $cart->setRelation('items', collect([$cartItem]));

        $order = Order::factory()->make(['id' => 100]);
        $order->setRelation('items', collect([$cartItem]));

        $dto = new OrderDTO(userId: 1, shippingAddress: [], billingAddress: []);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($cart);
        $cartRepo->shouldReceive('clear')->andReturn(true);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->andReturn($product);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('create')->andReturn($order);

        $this->makeService($orderRepo, $cartRepo, $productRepo)->createFromCart($dto);

        Bus::assertDispatched(ProcessOrderJob::class);
    }

    public function test_create_from_cart_calculates_totals_correctly(): void
    {
        Event::fake();
        Bus::fake();

        $product = Product::factory()->make([
            'id' => 5, 'active' => true, 'quantity' => 10, 'price' => 100.0, 'min_quantity' => 2,
        ]);

        $cartItem = CartItem::factory()->make(['product_id' => 5, 'quantity' => 3]);
        $cartItem->setRelation('product', $product);

        $cart = Cart::factory()->make(['id' => 1]);
        $cart->setRelation('items', collect([$cartItem]));

        $capturedOrderData = null;
        $order = Order::factory()->make(['id' => 100]);
        $order->setRelation('items', collect());

        $dto = new OrderDTO(userId: 1, shippingAddress: [], billingAddress: []);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($cart);
        $cartRepo->shouldReceive('clear')->andReturn(true);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->andReturn($product);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($orderData, $items) use (&$capturedOrderData) {
                $capturedOrderData = $orderData;

                return true;
            })
            ->andReturn($order);

        $this->makeService($orderRepo, $cartRepo, $productRepo)->createFromCart($dto);

        // subtotal = 3 * 100 = 300, tax = 30, total = 330
        $this->assertEquals(300.0, $capturedOrderData['subtotal']);
        $this->assertEquals(30.0, $capturedOrderData['tax']);
        $this->assertEquals(330.0, $capturedOrderData['total']);
        $this->assertEquals('pending', $capturedOrderData['status']);
    }

    // ── UpdateStatus ─────────────────────────────────────────────────────────

    public function test_update_status_delegates_to_repository(): void
    {
        $order = Order::factory()->make(['id' => 1, 'status' => 'pending']);
        $updatedOrder = Order::factory()->make(['id' => 1, 'status' => 'shipped']);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('updateStatus')->once()->with($order, 'shipped')->andReturn($updatedOrder);

        $result = $this->makeService($orderRepo)->updateStatus($order, 'shipped');

        $this->assertEquals('shipped', $result->status);
    }
}
