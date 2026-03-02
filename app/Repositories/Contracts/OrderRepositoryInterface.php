<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    /**
     * Get paginated orders for a specific user.
     */
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all orders paginated (admin).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find an order by ID.
     */
    public function findById(int $id): ?Order;

    /**
     * Find an order by ID for a specific user.
     */
    public function findByIdForUser(int $id, int $userId): ?Order;

    /**
     * Create a new order with items.
     *
     * @param  array<string, mixed>  $orderData
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(array $orderData, array $items): Order;

    /**
     * Update the status of an order.
     */
    public function updateStatus(Order $order, string $status): Order;
}
