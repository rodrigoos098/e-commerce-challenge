<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly ProductQueryBuilder $productQueryBuilder,
    ) {
    }

    /**
     * Get paginated products with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->productQueryBuilder
            ->build($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Find a product by ID.
     */
    public function findById(int $id): ?Product
    {
        return Product::query()->with(['category', 'tags'])->find($id);
    }

    /**
     * Find a product by slug.
     */
    public function findBySlug(string $slug): ?Product
    {
        return Product::query()->with(['category', 'tags'])->where('slug', $slug)->first();
    }

    /**
     * Find products by ID with row-level locks for checkout processing.
     *
     * @param  array<int>  $ids
     * @return Collection<int, Product>
     */
    public function findByIdsForUpdate(array $ids): Collection
    {
        return Product::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * Create a new product.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return Product::query()->create($data);
    }

    /**
     * Update an existing product.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh(['category', 'tags']);
    }

    /**
     * Soft delete a product.
     */
    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }

    /**
     * Get products with low stock.
     */
    public function lowStock(): Collection
    {
        return Product::query()
            ->with(['category'])
            ->lowStock()
            ->active()
            ->get();
    }

    /**
     * Sync tags for a product.
     *
     * @param  array<int>  $tagIds
     */
    public function syncTags(Product $product, array $tagIds): void
    {
        $product->tags()->sync($tagIds);
    }

    /**
     * Check if a slug already exists (including soft-deleted records).
     */
    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $query = Product::withTrashed()->where('slug', $slug);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * Get total count of active products.
     */
    public function totalCount(): int
    {
        return Product::query()->count();
    }
}
