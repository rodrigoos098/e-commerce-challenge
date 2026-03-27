<?php

namespace App\Services;

use App\DTOs\OrderDTO;
use App\Events\OrderCreated;
use App\Models\Order;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    use LogsActivity;

    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StockService $stockService,
    ) {
    }

    /**
     * Get paginated orders for a user.
     */
    public function paginateForUser(int $userId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->orderRepository->paginateForUser($userId, $perPage);
    }

    /**
     * Get all orders paginated (admin view).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->orderRepository->paginate($filters, $perPage);
    }

    /**
     * Find an order by ID.
     */
    public function findById(int $id): ?Order
    {
        return $this->orderRepository->findById($id);
    }

    /**
     * Find an order by ID for a specific user.
     */
    public function findByIdForUser(int $id, int $userId): ?Order
    {
        return $this->orderRepository->findByIdForUser($id, $userId);
    }

    /**
     * Create an order from the user's current cart.
     *
     * @throws ValidationException
     */
    public function createFromCart(OrderDTO $dto): Order
    {
        $cart = $this->cartRepository->findByUserId($dto->userId);

        if (! $cart || $cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => ['Cart is empty or not found.'],
            ]);
        }

        $order = DB::transaction(function () use ($dto, $cart): Order {
            $orderItems = [];
            $subtotal = 0;
            $requiredQuantities = $cart->items
                ->groupBy('product_id')
                ->map(fn ($items): int => (int) $items->sum('quantity'));
            $lockedProducts = $this->productRepository
                ->findByIdsForUpdate($requiredQuantities->keys()->map(fn ($id): int => (int) $id)->all())
                ->keyBy('id');

            foreach ($cart->items as $cartItem) {
                $product = $lockedProducts->get($cartItem->product_id);

                if (! $product || ! $product->active) {
                    throw ValidationException::withMessages([
                        'cart' => ["Product '{$cartItem->product?->name}' is no longer available."],
                    ]);
                }

                if ($product->quantity < $requiredQuantities->get($product->id, 0)) {
                    throw ValidationException::withMessages([
                        'cart' => ["Insufficient stock for product '{$product->name}'. Available: {$product->quantity}."],
                    ]);
                }

                $lineTotal = $product->price * $cartItem->quantity;
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $product->price,
                    'total_price' => $lineTotal,
                ];
            }

            $tax = round($subtotal * 0.1, 2);
            $shippingCost = 0;
            $total = $subtotal + $tax + $shippingCost;

            $orderData = array_merge($dto->toArray(), [
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shippingCost,
                'total' => $total,
            ]);

            $order = $this->orderRepository->create($orderData, $orderItems);

            foreach ($cart->items as $item) {
                /** @var \App\Models\Product|null $product */
                $product = $lockedProducts->get($item->product_id);

                if (! $product) {
                    continue;
                }

                $this->stockService->decreaseStockForLockedProduct(
                    product: $product,
                    quantity: $item->quantity,
                    orderId: $order->id,
                );
            }

            $this->cartRepository->clear($cart);

            event(new OrderCreated($order));

            return $order;
        });

        $this->logActivity('orders', 'Order created', [
            'order_id' => $order->id,
            'status' => $order->status,
            'total' => $order->total,
        ]);

        return $order;
    }

    /**
     * Update the status of an order.
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $previousStatus = $order->status;
        $updatedOrder = $this->orderRepository->updateStatus($order, $status);

        $this->logActivity('orders', 'Order status updated', [
            'order_id' => $updatedOrder->id,
            'previous_status' => $previousStatus,
            'status' => $updatedOrder->status,
        ]);

        return $updatedOrder;
    }

    /**
     * Get total count of all orders.
     */
    public function totalCount(): int
    {
        return $this->orderRepository->totalCount();
    }

    /**
     * Get total revenue (excluding cancelled orders).
     */
    public function totalRevenue(): float
    {
        return $this->orderRepository->totalRevenue();
    }

    /**
     * Get the most recent orders.
     */
    public function recent(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->orderRepository->recent($limit);
    }

    /**
     * Get a daily order summary for the admin dashboard.
     *
     * @return array<int, array{date: string, orders: int, revenue: float}>
     */
    public function dailySummary(int $days = 7): array
    {
        return $this->orderRepository->dailySummary($days);
    }
}
