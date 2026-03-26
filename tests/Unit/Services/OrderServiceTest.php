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
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
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
    ): OrderService {
        $orderRepo ??= Mockery::mock(OrderRepositoryInterface::class);
        $cartRepo ??= Mockery::mock(CartRepositoryInterface::class);
        $productRepo ??= Mockery::mock(ProductRepositoryInterface::class);
        $stockService ??= Mockery::mock(StockService::class);

        return new OrderService($orderRepo, $cartRepo, $productRepo, $stockService);
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

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findByIdsForUpdate')->with([5])->andReturn(new EloquentCollection([$product]));

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('create')->andReturn($order);

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldReceive('decreaseStockForLockedProduct')->once()->with($product, 2, 100);

        $this->makeService($orderRepo, $cartRepo, $productRepo, $stockService)->createFromCart($this->makeDto());

        Event::assertDispatched(OrderCreated::class);
    }

    public function test_create_from_cart_does_not_dispatch_stock_low_when_stock_service_handles_it(): void
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

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findByIdsForUpdate')->with([5])->andReturn(new EloquentCollection([$product]));

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('create')->andReturn($order);

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldReceive('decreaseStockForLockedProduct')->once()->with($product, 1, 100);

        $this->makeService($orderRepo, $cartRepo, $productRepo, $stockService)->createFromCart($this->makeDto());

        Event::assertNotDispatched(StockLow::class);
    }

    public function test_create_from_cart_decreases_stock_synchronously(): void
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

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findByIdsForUpdate')->with([5])->andReturn(new EloquentCollection([$product]));

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('create')->andReturn($order);

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldReceive('decreaseStockForLockedProduct')->once()->with($product, 3, 100);

        $this->makeService($orderRepo, $cartRepo, $productRepo, $stockService)->createFromCart($this->makeDto());
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

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findByIdsForUpdate')->with([5])->andReturn(new EloquentCollection([$product]));

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('create')
            ->once()
            ->withArgs(function (array $orderData, array $items) use (&$capturedOrderData): bool {
                $capturedOrderData = $orderData;

                return count($items) === 1;
            })
            ->andReturn($order);

        $stockService = Mockery::mock(StockService::class);
        $stockService->shouldReceive('decreaseStockForLockedProduct')->once()->with($product, 3, 100);

        $this->makeService($orderRepo, $cartRepo, $productRepo, $stockService)->createFromCart($this->makeDto());

        $this->assertEquals(300.0, $capturedOrderData['subtotal']);
        $this->assertEquals(30.0, $capturedOrderData['tax']);
        $this->assertEquals(330.0, $capturedOrderData['total']);
        $this->assertEquals('pending', $capturedOrderData['status']);
    }

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
