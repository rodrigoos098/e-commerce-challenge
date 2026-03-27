<?php

namespace App\Repositories\Contracts;

use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface StockMovementRepositoryInterface
{
    /**
     * Get paginated movements for a product.
     */
    public function paginateForProduct(int $productId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get all movements for a product.
     */
    public function forProduct(int $productId): Collection;

    /**
     * Create a new stock movement.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): StockMovement;
}
