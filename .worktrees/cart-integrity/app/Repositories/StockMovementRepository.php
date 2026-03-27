<?php

namespace App\Repositories;

use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StockMovementRepository implements StockMovementRepositoryInterface
{
    /**
     * Get paginated movements for a product.
     */
    public function paginateForProduct(int $productId, int $perPage = 15): LengthAwarePaginator
    {
        return StockMovement::query()
            ->with('product')
            ->where('product_id', $productId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all movements for a product.
     */
    public function forProduct(int $productId): Collection
    {
        return StockMovement::query()
            ->with('product')
            ->where('product_id', $productId)
            ->latest()
            ->get();
    }

    /**
     * Create a new stock movement.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): StockMovement
    {
        return StockMovement::query()->create($data);
    }
}
