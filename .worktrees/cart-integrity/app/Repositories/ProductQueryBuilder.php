<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class ProductQueryBuilder
{
    /**
     * Build a product query with the provided filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function build(array $filters = []): Builder
    {
        return $this->apply(Product::query()->with(['category', 'tags']), $filters);
    }

    /**
     * Apply product filters and sorting to an existing query.
     *
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters = []): Builder
    {
        if (isset($filters['search']) && trim((string) $filters['search']) !== '') {
            $search = trim((string) $filters['search']);

            $query->where(function (Builder $productQuery) use ($search): void {
                $productQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['category_id'])) {
            $categoryId = $filters['category_id'];
            $query->whereIn('category_id', function ($query) use ($categoryId) {
                $query->select('id')
                      ->from('categories')
                      ->where('id', $categoryId)
                      ->orWhere('parent_id', $categoryId);
            });
        }

        if (array_key_exists('active', $filters) && $filters['active'] !== null && $filters['active'] !== '') {
            $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $filters['active']);
        }

        if (! empty($filters['in_stock'])) {
            $query->inStock();
        }

        if (! empty($filters['low_stock'])) {
            $query->lowStock();
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $query->where('price', '<=', $filters['max_price']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $allowedSorts = ['name', 'price', 'quantity', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        return $query;
    }
}
