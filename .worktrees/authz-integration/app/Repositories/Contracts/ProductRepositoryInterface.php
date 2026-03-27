<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * Get paginated products with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a product by ID.
     */
    public function findById(int $id): ?Product;

    /**
     * Find a product by slug.
     */
    public function findBySlug(string $slug): ?Product;

    /**
     * Find products by ID with row-level locks for checkout processing.
     *
     * @param  array<int>  $ids
     * @return Collection<int, Product>
     */
    public function findByIdsForUpdate(array $ids): Collection;

    /**
     * Create a new product.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product;

    /**
     * Update an existing product.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product;

    /**
     * Soft delete a product.
     */
    public function delete(Product $product): bool;

    /**
     * Get products with low stock.
     */
    public function lowStock(): Collection;

    /**
     * Sync tags for a product.
     *
     * @param  array<int>  $tagIds
     */
    public function syncTags(Product $product, array $tagIds): void;

    /**
     * Check if a slug already exists (including soft-deleted records).
     */
    public function slugExists(string $slug, ?int $exceptId = null): bool;

    /**
     * Get total count of active products.
     */
    public function totalCount(): int;
}
