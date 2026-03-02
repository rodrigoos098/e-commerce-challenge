<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Get paginated orders for a specific user.
     */
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->with(['user', 'items.product'])
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all orders paginated (admin).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::query()->with(['user', 'items.product']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Find an order by ID.
     */
    public function findById(int $id): ?Order
    {
        return Order::query()->with(['user', 'items.product'])->find($id);
    }

    /**
     * Find an order by ID for a specific user.
     */
    public function findByIdForUser(int $id, int $userId): ?Order
    {
        return Order::query()
            ->with(['user', 'items.product'])
            ->where('user_id', $userId)
            ->find($id);
    }

    /**
     * Create a new order with items.
     *
     * @param  array<string, mixed>  $orderData
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(array $orderData, array $items): Order
    {
        /** @var Order $order */
        $order = Order::query()->create($orderData);

        foreach ($items as $item) {
            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'],
            ]);
        }

        return $order->load(['items.product']);
    }

    /**
     * Update the status of an order.
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $order->update(['status' => $status]);

        return $order->fresh(['items.product']);
    }
}
