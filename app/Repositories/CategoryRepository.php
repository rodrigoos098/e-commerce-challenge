<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    /**
     * Get all root categories with their children loaded (hierarchical tree).
     */
    public function tree(): Collection
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $this->loadActiveChildrenRecursively($categories);

        return $categories;
    }

    /**
     * Get all categories (flat list).
     */
    public function all(): Collection
    {
        return Category::query()->with(['parent'])->orderBy('name')->get();
    }

    /**
     * Find a category by ID.
     */
    public function findById(int $id): ?Category
    {
        $category = Category::query()->with(['parent'])->find($id);

        if (! $category instanceof Category) {
            return null;
        }

        $this->loadActiveChildrenRecursively(new Collection([$category]));

        return $category;
    }

    /**
     * Find a category by slug.
     */
    public function findBySlug(string $slug): ?Category
    {
        return Category::query()->with(['parent', 'children'])->where('slug', $slug)->first();
    }

    /**
     * Create a new category.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        return Category::query()->create($data);
    }

    /**
     * Update an existing category.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->fresh(['parent', 'children']);
    }

    /**
     * Delete a category.
     */
    public function delete(Category $category): bool
    {
        $categoryIds = $this->descendantIdsFor([$category->id]);

        return Category::query()
            ->whereIn('id', $categoryIds)
            ->update(['active' => false]) > 0;
    }

    /**
     * Check if a slug already exists.
     */
    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $query = Category::query()->where('slug', $slug);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function loadActiveChildrenRecursively(Collection $categories): void
    {
        if ($categories->isEmpty()) {
            return;
        }

        $categories->load([
            'children' => function ($query): void {
                $query->where('active', true)->orderBy('name');
            },
        ]);

        $this->loadActiveChildrenRecursively(new Collection($categories->pluck('children')->flatten()->all()));
    }

    /**
     * @param  array<int>  $rootIds
     * @return array<int>
     */
    private function descendantIdsFor(array $rootIds): array
    {
        $categoryIds = $rootIds;
        $parentIds = $rootIds;

        while ($parentIds !== []) {
            $childIds = Category::query()
                ->whereIn('parent_id', $parentIds)
                ->pluck('id')
                ->all();

            $childIds = array_values(array_diff($childIds, $categoryIds));

            if ($childIds === []) {
                break;
            }

            $categoryIds = [...$categoryIds, ...$childIds];
            $parentIds = $childIds;
        }

        return $categoryIds;
    }
}
