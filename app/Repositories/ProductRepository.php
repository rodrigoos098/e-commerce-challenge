<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Get paginated products with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query()->with(['category', 'tags']);

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters): void {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $query->inStock();
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->lowStock();
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $allowedSorts = ['name', 'price', 'quantity', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        return $query->paginate($perPage);
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
}
