<?php

namespace App\Services;

use App\DTOs\ProductDTO;
use App\Events\ProductCreated;
use App\Events\StockLow;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {
    }

    /**
     * Get paginated products with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = 'products.list.'.md5(serialize($filters)).'.'.$perPage;

        return Cache::tags(['products'])->remember($cacheKey, now()->addHour(), fn () => $this->productRepository->paginate($filters, $perPage));
    }

    /**
     * Find a product by ID.
     */
    public function findById(int $id): ?Product
    {
        return Cache::tags(['products'])->remember(
            "products.{$id}",
            now()->addHour(),
            fn () => $this->productRepository->findById($id),
        );
    }

    /**
     * Find a product by slug.
     */
    public function findBySlug(string $slug): ?Product
    {
        return Cache::tags(['products'])->remember(
            "products.slug.{$slug}",
            now()->addHour(),
            fn () => $this->productRepository->findBySlug($slug),
        );
    }

    /**
     * Create a new product.
     */
    public function create(ProductDTO $dto): Product
    {
        $data = $dto->toArray();

        if (! empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['slug']);
        } else {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $product = $this->productRepository->create($data);

        if (! empty($dto->tagIds)) {
            $this->productRepository->syncTags($product, $dto->tagIds);
        }

        $this->invalidateCache();

        event(new ProductCreated($product));

        if ($product->quantity <= $product->min_quantity) {
            event(new StockLow($product));
        }

        return $this->productRepository->findById($product->id);
    }

    /**
     * Update an existing product.
     */
    public function update(Product $product, ProductDTO $dto): Product
    {
        $data = $dto->toArray();

        if (! empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], $product->id);
        } elseif (array_key_exists('slug', $data)) {
            if (isset($data['name']) && $data['name'] !== $product->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $product->id);
            } else {
                unset($data['slug']);
            }
        } elseif (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $product->id);
        }

        $updated = $this->productRepository->update($product, $data);

        if (isset($dto->tagIds)) {
            $this->productRepository->syncTags($updated, $dto->tagIds);
        }

        $this->invalidateCache();

        if ($updated->quantity <= $updated->min_quantity) {
            event(new StockLow($updated));
        }

        return $updated;
    }

    /**
     * Soft delete a product.
     */
    public function delete(Product $product): bool
    {
        $result = $this->productRepository->delete($product);
        $this->invalidateCache();

        return $result;
    }

    /**
     * Get products with low stock.
     */
    public function lowStock(): Collection
    {
        return $this->productRepository->lowStock();
    }

    /**
     * Get total count of products.
     */
    public function totalCount(): int
    {
        return $this->productRepository->totalCount();
    }

    /**
     * Flush all cached product payloads.
     */
    public function flushCache(): void
    {
        $this->invalidateCache();
    }

    /**
     * Generate a unique slug for a product.
     */
    private function generateUniqueSlug(string $name, ?int $exceptId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while ($this->slugExists($slug, $exceptId)) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists.
     */
    private function slugExists(string $slug, ?int $exceptId = null): bool
    {
        return $this->productRepository->slugExists($slug, $exceptId);
    }

    /**
     * Invalidate all products cache entries via tag flush.
     */
    private function invalidateCache(): void
    {
        Cache::tags(['products'])->flush();
    }
}
