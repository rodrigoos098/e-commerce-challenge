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
        return Category::query()
            ->with(['children.children'])
            ->whereNull('parent_id')
            ->where('active', true)
            ->orderBy('name')
            ->get();
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
        return Category::query()->with(['parent', 'children'])->find($id);
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
        return (bool) $category->delete();
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
}
