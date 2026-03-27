<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {
    }

    /**
     * Get the category tree (cached 24h).
     */
    public function tree(): Collection
    {
        return Cache::tags(['categories'])->remember(
            'categories.tree',
            now()->addDay(),
            fn () => $this->categoryRepository->tree(),
        );
    }

    /**
     * Get all categories flat list.
     */
    public function all(): Collection
    {
        return Cache::tags(['categories'])->remember(
            'categories.all',
            now()->addDay(),
            fn () => $this->categoryRepository->all(),
        );
    }

    /**
     * Find a category by ID.
     */
    public function findById(int $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }

    /**
     * Create a new category.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        if (! empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['slug']);
        } else {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $category = $this->categoryRepository->create($data);
        $this->invalidateCache();

        return $category;
    }

    /**
     * Update an existing category.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        if (! empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], $category->id);
        } elseif (array_key_exists('slug', $data)) {
            if (isset($data['name']) && $data['name'] !== $category->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $category->id);
            } else {
                unset($data['slug']);
            }
        } elseif (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $category->id);
        }

        $updated = $this->categoryRepository->update($category, $data);
        $this->invalidateCache();

        return $updated;
    }

    /**
     * Delete a category.
     */
    public function delete(Category $category): bool
    {
        $result = $this->categoryRepository->delete($category);
        $this->invalidateCache();

        return $result;
    }

    /**
     * Generate a unique slug for a category.
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
        return $this->categoryRepository->slugExists($slug, $exceptId);
    }

    /**
     * Invalidate all categories cache entries via tag flush.
     */
    private function invalidateCache(): void
    {
        Cache::tags(['categories'])->flush();
    }
}
