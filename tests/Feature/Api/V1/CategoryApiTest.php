<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_guest_can_list_categories(): void
    {
        Category::factory()->count(3)->create(['active' => true]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_categories_are_returned_as_tree(): void
    {
        $parent = Category::factory()->create(['active' => true, 'parent_id' => null]);
        Category::factory()->create(['parent_id' => $parent->id, 'active' => true]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200);
        // Only root category should be in top-level data
        $this->assertCount(1, $response->json('data'));
    }

    public function test_inactive_categories_are_not_listed_in_tree(): void
    {
        Category::factory()->create(['active' => true]);
        Category::factory()->inactive()->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_guest_can_view_single_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $category->id);
    }

    public function test_returns_404_for_nonexistent_category(): void
    {
        $response = $this->getJson('/api/v1/categories/9999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message']);
    }

    // ── Products by Category ──────────────────────────────────────────────────

    public function test_guest_can_list_products_by_category(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id, 'active' => true]);
        Product::factory()->create(['active' => true]); // different category

        $response = $this->getJson("/api/v1/categories/{$category->id}/products");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data', 'meta', 'links']);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_products_by_category_returns_404_for_unknown_category(): void
    {
        $response = $this->getJson('/api/v1/categories/9999/products');

        $response->assertStatus(404);
    }

    public function test_products_by_category_can_be_searched(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Notebook Dell',
            'slug' => 'notebook-dell',
            'active' => true,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Mouse Logitech',
            'slug' => 'mouse-logitech',
            'active' => true,
        ]);

        $response = $this->getJson("/api/v1/categories/{$category->id}/products?search=Notebook");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Notebook Dell', $response->json('data.0.name'));
    }

    public function test_products_by_category_are_paginated(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(10)->create(['category_id' => $category->id, 'active' => true]);

        $response = $this->getJson("/api/v1/categories/{$category->id}/products?per_page=5");

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }
}
