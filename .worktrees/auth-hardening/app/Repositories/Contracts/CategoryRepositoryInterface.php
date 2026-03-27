<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    /**
     * Get all active categories as a hierarchical tree.
     */
    public function tree(): Collection;

    /**
     * Get all categories (flat list).
     */
    public function all(): Collection;

    /**
     * Find a category by ID.
     */
    public function findById(int $id): ?Category;

    /**
     * Find a category by slug.
     */
    public function findBySlug(string $slug): ?Category;

    /**
     * Create a new category.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category;

    /**
     * Update an existing category.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category;

    /**
     * Delete a category.
     */
    public function delete(Category $category): bool;

    /**
     * Check if a slug already exists.
     */
    public function slugExists(string $slug, ?int $exceptId = null): bool;
}
