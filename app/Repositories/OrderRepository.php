<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Carbon\CarbonImmutable;
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

        if (isset($filters['search']) && trim((string) $filters['search']) !== '') {
            $search = trim((string) $filters['search']);

            if (is_numeric($search)) {
                $query->where('id', (int) $search);
            } else {
                $query->whereHas('user', function ($userQuery) use ($search): void {
                    $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }
        }

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

    /**
     * Get total count of all orders.
     */
    public function totalCount(): int
    {
        return Order::query()->count();
    }

    /**
     * Get total revenue (excluding cancelled orders).
     */
    public function totalRevenue(): float
    {
        return (float) Order::query()->where('status', '!=', 'cancelled')->sum('total');
    }

    /**
     * Get the most recent orders.
     */
    public function recent(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return Order::query()
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get a daily order summary for the last N days.
     *
     * @return array<int, array{date: string, orders: int, revenue: float}>
     */
    public function dailySummary(int $days = 7): array
    {
        $startDate = CarbonImmutable::today()->subDays($days - 1);
        $orders = Order::query()
            ->where('created_at', '>=', $startDate->startOfDay())
            ->where('status', '!=', 'cancelled')
            ->get(['created_at', 'total']);

        return collect(range(0, $days - 1))
            ->map(function (int $offset) use ($orders, $startDate): array {
                $date = $startDate->addDays($offset);
                $ordersForDay = $orders->filter(
                    fn (Order $order): bool => $order->created_at->isSameDay($date)
                );

                return [
                    'date' => $date->toDateString(),
                    'orders' => $ordersForDay->count(),
                    'revenue' => round((float) $ordersForDay->sum('total'), 2),
                ];
            })
            ->all();
    }
}
