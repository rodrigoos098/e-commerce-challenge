<?php

namespace Tests\Unit\Services;

use App\DTOs\OrderDTO;
use App\Events\OrderCreated;
use App\Events\StockLow;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\CartTotalsService;
use App\Services\OrderService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
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
        ?StockService $stockService = null,
        ?CartTotalsService $cartTotalsService = null,
    ): OrderService {
        $orderRepo ??= Mockery::mock(OrderRepositoryInterface::class);
        $cartRepo ??= Mockery::mock(CartRepositoryInterface::class);
        $productRepo ??= Mockery::mock(ProductRepositoryInterface::class);
        $stockService ??= Mockery::mock(StockService::class);
        $cartTotalsService ??= new CartTotalsService();

        return new OrderService($orderRepo, $cartRepo, $productRepo, $stockService, $cartTotalsService);
    }

    /**
     * @param  Collection<int, CartItem>  $items
     */
    private function makeCart(Collection $items): Cart
    {
        $cart = Cart::factory()->make(['id' => 1]);
        $cart->setRelation('items', $items);

        return $cart;
    }

    private function makeCartItem(Product $product, int $quantity): CartItem
    {
        $cartItem = CartItem::factory()->make([
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
        $cartItem->setRelation('product', $product);

        return $cartItem;
    }

    private function makeDto(): OrderDTO
    {
        return new OrderDTO(
            userId: 1,
            shippingAddress: ['street' => '1 Main', 'city' => 'SP', 'state' => 'SP', 'zip_code' => '01234', 'country' => 'BR'],
            billingAddress: ['street' => '1 Main', 'city' => 'SP', 'state' => 'SP', 'zip_code' => '01234', 'country' => 'BR'],
        );
    }

    public function test_create_from_cart_throws_when_no_cart_found(): void
    {
        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn(null);

        $this->expectException(ValidationException::class);

        $this->makeService(cartRepo: $cartRepo)->createFromCart($this->makeDto());
    }

    public function test_create_from_cart_throws_when_cart_is_empty(): void
    {
        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($this->makeCart(collect()));

        $this->expectException(ValidationException::class);

        $this->makeService(cartRepo: $cartRepo)->createFromCart($this->makeDto());
    }

    public function test_create_from_cart_throws_when_product_inactive(): void
    {
        $product = Product::factory()->make([
            'id' => 5,
            'active' => false,
            'quantity' => 10,
            'price' => 50.0,
        ]);
        $cart = $this->makeCart(collect([$this->makeCartItem($product, 1)]));

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($cart);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findByIdsForUpdate')->with([5])->andReturn(new EloquentCollection([$product]));

        $this->expectException(ValidationException::class);

        $this->makeService(cartRepo: $cartRepo, productRepo: $productRepo)->createFromCart($this->makeDto());
    }

    public function test_create_from_cart_throws_when_insufficient_stock(): void
    {
        $product = Product::factory()->make([
            'id' => 5,
            'active' => true,
            'quantity' => 1,
            'price' => 50.0,
            'name' => 'Produto X',
        ]);
        $cart = $this->makeCart(collect([$this->makeCartItem($product, 5)]));

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($cart);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findByIdsForUpdate')->with([5])->andReturn(new EloquentCollection([$product]));

        $this->expectException(ValidationException::class);

        $this->makeService(cartRepo: $cartRepo, productRepo: $productRepo)->createFromCart($this->makeDto());
    }

    public function test_create_from_cart_fires_order_created_event(): void
    {
        Event::fake();

        $product = Product::factory()->make([
            'id' => 5,
            'active' => true,
            'quantity' => 10,
            'price' => 50.0,
            'min_quantity' => 2,
        ]);
        $cart = $this->makeCart(collect([$this->makeCartItem($product, 2)]));
        $order = Order::factory()->make(['id' => 100]);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($cart);
        $cartRepo->shouldReceive('clear')->once()->andReturn(true);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('create')->andReturn($order);

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldNotReceive('decreaseStockForLockedProduct');

        $this->makeService($orderRepo, $cartRepo, stockService: $stockService)->createFromCart($this->makeDto());

        Event::assertDispatched(OrderCreated::class);
    }

    public function test_create_from_cart_does_not_dispatch_stock_low_before_async_processing(): void
    {
        Event::fake();

        $product = Product::factory()->make([
            'id' => 5,
            'active' => true,
            'quantity' => 2,
            'price' => 50.0,
            'min_quantity' => 2,
        ]);
        $cart = $this->makeCart(collect([$this->makeCartItem($product, 1)]));
        $order = Order::factory()->make(['id' => 100]);
        $order->setRelation('items', new EloquentCollection([
            OrderItem::factory()->make(['product_id' => $product->id]),
        ]));

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($cart);
        $cartRepo->shouldReceive('clear')->once()->andReturn(true);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('create')->andReturn($order);

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldNotReceive('decreaseStockForLockedProduct');

        $this->makeService($orderRepo, $cartRepo, stockService: $stockService)->createFromCart($this->makeDto());

        Event::assertNotDispatched(StockLow::class);
    }

    public function test_create_from_cart_defers_stock_processing_until_async_handler(): void
    {
        Event::fake();

        $product = Product::factory()->make([
            'id' => 5,
            'active' => true,
            'quantity' => 10,
            'price' => 50.0,
            'min_quantity' => 2,
        ]);
        $cart = $this->makeCart(collect([$this->makeCartItem($product, 3)]));
        $order = Order::factory()->make(['id' => 100]);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($cart);
        $cartRepo->shouldReceive('clear')->once()->andReturn(true);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('create')->andReturn($order);

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldNotReceive('decreaseStockForLockedProduct');

        $this->makeService($orderRepo, $cartRepo, stockService: $stockService)->createFromCart($this->makeDto());
    }

    public function test_create_from_cart_calculates_totals_correctly(): void
    {
        Event::fake();

        $product = Product::factory()->make([
            'id' => 5,
            'active' => true,
            'quantity' => 10,
            'price' => 100.0,
            'min_quantity' => 2,
        ]);
        $cart = $this->makeCart(collect([$this->makeCartItem($product, 3)]));
        $capturedOrderData = null;
        $order = Order::factory()->make(['id' => 100]);

        $cartRepo = Mockery::mock(CartRepositoryInterface::class);
        $cartRepo->shouldReceive('findByUserId')->andReturn($cart);
        $cartRepo->shouldReceive('clear')->once()->andReturn(true);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $orderData, array $items) use (&$capturedOrderData): bool {
                $capturedOrderData = $orderData;

                return count($items) === 1;
            })
            ->andReturn($order);

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldNotReceive('decreaseStockForLockedProduct');

        $this->makeService($orderRepo, $cartRepo, stockService: $stockService)->createFromCart($this->makeDto());

        $this->assertEquals(300.0, $capturedOrderData['subtotal']);
        $this->assertEquals(30.0, $capturedOrderData['tax']);
        $this->assertEquals(0.0, $capturedOrderData['shipping_cost']);
        $this->assertEquals(330.0, $capturedOrderData['total']);
        $this->assertEquals('processing', $capturedOrderData['status']);
    }

    public function test_update_status_delegates_to_repository(): void
    {
        $order = Order::factory()->make(['id' => 1, 'status' => 'pending']);
        $updatedOrder = Order::factory()->make(['id' => 1, 'status' => 'processing']);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('updateStatus')->once()->with($order, 'processing')->andReturn($updatedOrder);

        $result = $this->makeService($orderRepo)->updateStatus($order, 'processing');

        $this->assertEquals('processing', $result->status);
    }

    public function test_update_status_restores_stock_when_order_is_cancelled(): void
    {
        $product = Product::factory()->create();
        $orderItem = OrderItem::factory()->make([
            'order_id' => 1,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        $orderItem->setRelation('product', $product);

        $order = Order::factory()->make(['id' => 1, 'status' => 'processing']);
        $order->setRelation('items', new EloquentCollection([$orderItem]));
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'type' => 'venda',
            'quantity' => 3,
            'reference_type' => 'order',
            'reference_id' => 1,
        ]);

        $updatedOrder = Order::factory()->make(['id' => 1, 'status' => 'cancelled']);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('updateStatus')->once()->with($order, 'cancelled')->andReturn($updatedOrder);

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldReceive('restoreStockFromCancelledOrder')->once()->with($product->id, 3, 1);

        $result = $this->makeService($orderRepo, stockService: $stockService)->updateStatus($order, 'cancelled');

        $this->assertEquals('cancelled', $result->status);
    }

    public function test_update_status_restores_stock_for_each_order_item_when_cancelled(): void
    {
        $firstProduct = Product::factory()->create();
        $secondProduct = Product::factory()->create();

        $firstItem = OrderItem::factory()->make([
            'order_id' => 1,
            'product_id' => $firstProduct->id,
            'quantity' => 3,
        ]);
        $firstItem->setRelation('product', $firstProduct);

        $secondItem = OrderItem::factory()->make([
            'order_id' => 1,
            'product_id' => $secondProduct->id,
            'quantity' => 2,
        ]);
        $secondItem->setRelation('product', $secondProduct);

        $order = Order::factory()->make(['id' => 1, 'status' => 'processing']);
        $order->setRelation('items', new EloquentCollection([$firstItem, $secondItem]));
        StockMovement::factory()->create([
            'product_id' => $firstProduct->id,
            'type' => 'venda',
            'quantity' => 3,
            'reference_type' => 'order',
            'reference_id' => 1,
        ]);
        StockMovement::factory()->create([
            'product_id' => $secondProduct->id,
            'type' => 'venda',
            'quantity' => 2,
            'reference_type' => 'order',
            'reference_id' => 1,
        ]);

        $updatedOrder = Order::factory()->make(['id' => 1, 'status' => 'cancelled']);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('updateStatus')->once()->with($order, 'cancelled')->andReturn($updatedOrder);

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldReceive('restoreStockFromCancelledOrder')->once()->with($firstProduct->id, 3, 1);
        $stockService->shouldReceive('restoreStockFromCancelledOrder')->once()->with($secondProduct->id, 2, 1);

        $this->makeService($orderRepo, stockService: $stockService)->updateStatus($order, 'cancelled');
    }

    public function test_update_status_does_not_restore_stock_when_order_is_already_cancelled(): void
    {
        $product = Product::factory()->make(['id' => 5]);
        $orderItem = OrderItem::factory()->make([
            'order_id' => 1,
            'product_id' => 5,
            'quantity' => 3,
        ]);
        $orderItem->setRelation('product', $product);

        $order = Order::factory()->make(['id' => 1, 'status' => 'cancelled']);
        $order->setRelation('items', new EloquentCollection([$orderItem]));

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldNotReceive('updateStatus');

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldNotReceive('restoreStockFromCancelledOrder');

        $result = $this->makeService($orderRepo, stockService: $stockService)->updateStatus($order, 'cancelled');

        $this->assertEquals('cancelled', $result->status);
    }

    public function test_update_status_throws_when_transition_is_not_allowed(): void
    {
        $order = Order::factory()->make(['id' => 1, 'status' => 'shipped']);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldNotReceive('updateStatus');

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldNotReceive('restoreStockFromCancelledOrder');

        $this->expectException(ValidationException::class);

        $this->makeService($orderRepo, stockService: $stockService)->updateStatus($order, 'cancelled');
    }

    public function test_process_pending_order_decreases_stock_and_updates_status(): void
    {
        $product = Product::factory()->make([
            'id' => 5,
            'active' => true,
            'quantity' => 10,
            'price' => 50.0,
        ]);
        $orderItem = OrderItem::factory()->make([
            'order_id' => 100,
            'product_id' => 5,
            'quantity' => 2,
        ]);
        $order = Order::factory()->make(['id' => 100, 'status' => 'processing']);
        $order->setRelation('items', new EloquentCollection([$orderItem]));
        $processedOrder = Order::factory()->make(['id' => 100, 'status' => 'pending']);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('findById')->once()->with(100)->andReturn($order);
        $orderRepo->shouldReceive('updateStatus')->once()->with($order, 'pending')->andReturn($processedOrder);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findByIdsForUpdate')->once()->with([5])->andReturn(new EloquentCollection([$product]));

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldReceive('decreaseStockForLockedProduct')->once()->with($product, 2, 100);

        $result = $this->makeService($orderRepo, productRepo: $productRepo, stockService: $stockService)->processPendingOrder($order);

        $this->assertSame('pending', $result->status);
    }

    public function test_process_pending_order_cancels_when_stock_is_unavailable(): void
    {
        $product = Product::factory()->make([
            'id' => 5,
            'active' => true,
            'quantity' => 1,
            'price' => 50.0,
        ]);
        $orderItem = OrderItem::factory()->make([
            'order_id' => 100,
            'product_id' => 5,
            'quantity' => 2,
        ]);
        $order = Order::factory()->make(['id' => 100, 'status' => 'processing']);
        $order->setRelation('items', new EloquentCollection([$orderItem]));
        $cancelledOrder = Order::factory()->make(['id' => 100, 'status' => 'cancelled']);

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('findById')->once()->with(100)->andReturn($order);
        $orderRepo->shouldReceive('updateStatus')->once()->with($order, 'cancelled')->andReturn($cancelledOrder);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findByIdsForUpdate')->once()->with([5])->andReturn(new EloquentCollection([$product]));

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldNotReceive('decreaseStockForLockedProduct');

        $result = $this->makeService($orderRepo, productRepo: $productRepo, stockService: $stockService)->processPendingOrder($order);

        $this->assertSame('cancelled', $result->status);
    }
}
