<?php

namespace App\Services;

use App\DTOs\OrderDTO;
use App\Events\OrderCreated;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
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
        private readonly CartTotalsService $cartTotalsService,
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

            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;

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

            $totals = $this->cartTotalsService->calculate($cart->items);

            $orderData = array_merge($dto->toArray(), [
                'status' => 'processing',
                'subtotal' => $totals['subtotal'],
                'tax' => $totals['tax'],
                'shipping_cost' => $totals['shipping_cost'],
                'total' => $totals['total'],
            ]);

            $order = $this->orderRepository->create($orderData, $orderItems);

            $this->cartRepository->clear($cart);

            return $order;
        });

        event(new OrderCreated($order));

        $order = $order->fresh(['items.product']) ?? $order;

        $this->logActivity('orders', 'Order created', [
            'order_id' => $order->id,
            'status' => $order->status,
            'total' => $order->total,
        ]);

        return $order;
    }

    /**
     * Process a pending order asynchronously after it has been created.
     */
    public function processPendingOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $freshOrder = $this->orderRepository->findById($order->id);

            if (! $freshOrder || $freshOrder->status !== 'processing') {
                return $freshOrder ?? $order->loadMissing(['items.product', 'user']);
            }

            $requiredQuantities = $freshOrder->items
                ->groupBy('product_id')
                ->map(fn ($items): int => (int) $items->sum('quantity'));
            $lockedProducts = $this->productRepository
                ->findByIdsForUpdate($requiredQuantities->keys()->map(fn ($id): int => (int) $id)->all())
                ->keyBy('id');

            foreach ($freshOrder->items as $item) {
                /** @var \App\Models\Product|null $product */
                $product = $lockedProducts->get($item->product_id);

                if (! $product || ! $product->active || $product->quantity < $requiredQuantities->get($item->product_id, 0)) {
                    return $this->orderRepository->updateStatus($freshOrder, 'cancelled');
                }
            }

            foreach ($freshOrder->items as $item) {
                /** @var \App\Models\Product $product */
                $product = $lockedProducts->get($item->product_id);

                $this->stockService->decreaseStockForLockedProduct(
                    product: $product,
                    quantity: $item->quantity,
                    orderId: $freshOrder->id,
                );
            }

            return $this->orderRepository->updateStatus($freshOrder, 'pending');
        });
    }

    /**
     * Update the status of an order.
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $previousStatus = $order->status;

        if ($previousStatus === $status) {
            return $order->loadMissing(['items.product']);
        }

        if (! $order->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot change order status from '{$previousStatus}' to '{$status}'."],
            ]);
        }

        $updatedOrder = DB::transaction(function () use ($order, $status, $previousStatus): Order {
            $order->loadMissing(['items.product']);

            $orderAlreadyReducedStock = StockMovement::query()
                ->where('reference_type', 'order')
                ->where('reference_id', $order->id)
                ->where('type', 'venda')
                ->exists();

            if ($status === 'cancelled' && $previousStatus !== 'cancelled' && $orderAlreadyReducedStock) {
                $order->items->each(function (OrderItem $item) use ($order): void {
                    $this->stockService->restoreStockFromCancelledOrder(
                        productId: $item->product_id,
                        quantity: $item->quantity,
                        orderId: $order->id,
                    );
                });
            }

            return $this->orderRepository->updateStatus($order, $status);
        });

        $this->logActivity('orders', 'Order status updated', [
            'order_id' => $updatedOrder->id,
            'previous_status' => $previousStatus,
            'status' => $updatedOrder->status,
        ]);

        return $updatedOrder;
    }

    /**
     * Cancel an order through the standard status transition flow.
     */
    public function cancel(Order $order): Order
    {
        return $this->updateStatus($order, 'cancelled');
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
