<?php

namespace App\Services;

use App\DTOs\OrderDTO;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderDelivered;
use App\Events\OrderPaymentConfirmed;
use App\Events\OrderShipped;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Traits\LogsActivity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

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
        if (! $dto->paymentSimulated) {
            throw ValidationException::withMessages([
                'payment_simulated' => ['Simule o pagamento antes de concluir o pedido.'],
            ]);
        }

        try {
            $order = DB::transaction(function () use ($dto): Order {
                $cart = $this->cartRepository->findByUserIdForUpdate($dto->userId);

                if (! $cart || $cart->items->isEmpty()) {
                    throw ValidationException::withMessages([
                        'cart' => ['Nao ha itens disponiveis no carrinho para finalizar o pedido. Se voce acabou de enviar a compra, aguarde a confirmacao do pedido.'],
                    ]);
                }

                $requiredQuantities = $cart->items
                    ->groupBy('product_id')
                    ->map(fn (Collection $items): int => (int) $items->sum('quantity'));

                $lockedProducts = $this->productRepository
                    ->findByIdsForUpdate($requiredQuantities->keys()->map(fn ($id): int => (int) $id)->all())
                    ->keyBy('id');

                $orderItems = [];

                foreach ($cart->items as $cartItem) {
                    /** @var Product|null $product */
                    $product = $lockedProducts->get($cartItem->product_id);

                    if (! $product || ! $product->active) {
                        throw ValidationException::withMessages([
                            'cart' => [sprintf(
                                'O produto %s nao esta mais disponivel para compra.',
                                $cartItem->product?->name ? "\"{$cartItem->product->name}\"" : 'selecionado',
                            )],
                        ]);
                    }

                    $requiredQuantity = $requiredQuantities->get($product->id, 0);

                    if ($product->quantity < $requiredQuantity) {
                        throw ValidationException::withMessages([
                            'cart' => ["O produto \"{$product->name}\" possui apenas {$product->quantity} unidade(s) disponivel(is). Ajuste o carrinho e tente novamente."],
                        ]);
                    }

                    $lineTotal = $product->price * $cartItem->quantity;

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $product->price,
                        'total_price' => $lineTotal,
                    ];
                }

                $totals = $this->cartTotalsService->calculate(
                    $cart->items,
                    (string) data_get($dto->shippingAddress, 'zip_code', ''),
                );

                $orderData = array_merge($dto->toArray(), [
                    'status' => Order::INITIAL_STATUS,
                    'payment_status' => $dto->paymentSimulated ? 'paid' : Order::INITIAL_PAYMENT_STATUS,
                    'payment_method' => $dto->paymentSimulated ? Order::MOCK_PAYMENT_METHOD : null,
                    'paid_at' => $dto->paymentSimulated ? Carbon::now() : null,
                    'subtotal' => $totals['subtotal'],
                    'tax' => $totals['tax'],
                    'shipping_cost' => $totals['shipping_cost'],
                    'total' => $totals['total'],
                ]);

                $order = $this->orderRepository->create($orderData, $orderItems);

                foreach ($order->items as $item) {
                    /** @var Product $product */
                    $product = $lockedProducts->get($item->product_id);

                    $this->stockService->decreaseStockForLockedProduct(
                        product: $product,
                        quantity: $item->quantity,
                        orderId: $order->id,
                    );
                }

                $this->cartRepository->clear($cart);

                return $order->fresh(['items.product', 'user']) ?? $order;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'order' => ['Nao foi possivel finalizar o pedido agora. Nenhuma alteracao definitiva foi aplicada; tente novamente em instantes.'],
            ]);
        }

        $order = $order->fresh(['items.product', 'user']) ?? $order;

        event(new OrderCreated($order));
        event(new OrderPaymentConfirmed($order));

        $this->logActivity('orders', 'Order created', [
            'order_id' => $order->id,
            'status' => $order->status,
            'total' => $order->total,
        ]);

        return $order;
    }

    /**
     * Load the latest persisted order state for asynchronous follow-up work.
     */
    public function processPendingOrder(Order $order): Order
    {
        return $this->orderRepository->findById($order->id) ?? $order->loadMissing(['items.product', 'user']);
    }

    /**
     * Update the status of an order.
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $previousStatus = $order->status;

        if ($previousStatus === $status) {
            return $order->loadMissing(['items.product', 'user']);
        }

        if (! $order->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot change order status from '{$previousStatus}' to '{$status}'."],
            ]);
        }

        $updatedOrder = DB::transaction(function () use ($order, $status, $previousStatus): Order {
            $order->loadMissing(['items.product']);

            $orderAlreadyReducedStock = StockMovement::query()
                ->whereIn('reference_type', ['order', Order::class])
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

        $updatedOrder = $updatedOrder->fresh(['items.product', 'user']) ?? $updatedOrder->loadMissing(['items.product', 'user']);

        $this->logActivity('orders', 'Order status updated', [
            'order_id' => $updatedOrder->id,
            'previous_status' => $previousStatus,
            'status' => $updatedOrder->status,
        ]);

        $this->dispatchStatusChangedEvent($updatedOrder);

        return $updatedOrder;
    }

    /**
     * Dispatch transactional notification events for status changes.
     */
    private function dispatchStatusChangedEvent(Order $order): void
    {
        match ($order->status) {
            'cancelled' => event(new OrderCancelled($order)),
            'shipped' => event(new OrderShipped($order)),
            'delivered' => event(new OrderDelivered($order)),
            default => null,
        };
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
