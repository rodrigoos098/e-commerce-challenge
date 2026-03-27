<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function createCustomer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        return $user;
    }

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

    public function test_guest_cannot_view_inactive_category(): void
    {
        $category = Category::factory()->inactive()->create();

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_guest_cannot_view_active_category_when_an_ancestor_is_inactive(): void
    {
        $inactiveParent = Category::factory()->inactive()->create();
        $category = Category::factory()->create(['parent_id' => $inactiveParent->id, 'active' => true]);

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_guest_view_single_category_hides_inactive_children(): void
    {
        $category = Category::factory()->create();
        $activeChild = Category::factory()->create(['parent_id' => $category->id, 'active' => true]);
        Category::factory()->inactive()->create(['parent_id' => $category->id]);

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.children')
            ->assertJsonPath('data.children.0.id', $activeChild->id);
    }

    public function test_guest_view_single_category_excludes_inactive_child_branch_even_with_active_grandchildren(): void
    {
        $category = Category::factory()->create(['active' => true]);
        $activeChild = Category::factory()->create(['parent_id' => $category->id, 'active' => true]);
        $inactiveChild = Category::factory()->inactive()->create(['parent_id' => $category->id]);
        Category::factory()->create(['parent_id' => $inactiveChild->id, 'active' => true]);

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.children')
            ->assertJsonPath('data.children.0.id', $activeChild->id);

        $this->assertNotContains($inactiveChild->id, collect($response->json('data.children'))->pluck('id')->all());
    }

    public function test_guest_cannot_view_active_category_when_a_higher_ancestor_is_inactive(): void
    {
        $inactiveAncestor = Category::factory()->inactive()->create();
        $activeParent = Category::factory()->create(['parent_id' => $inactiveAncestor->id, 'active' => true]);
        $category = Category::factory()->create(['parent_id' => $activeParent->id, 'active' => true]);

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    // ── Store (admin) ─────────────────────────────────────────────────────────

    public function test_guest_cannot_create_category(): void
    {
        $response = $this->postJson('/api/v1/categories', ['name' => 'Nova Categoria']);

        $response->assertStatus(401);
    }

    public function test_customer_cannot_create_category(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/categories', ['name' => 'Nova Categoria']);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_category(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/categories', [
                'name' => 'Eletrônicos',
                'description' => 'Categoria de eletrônicos.',
                'active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'slug']]);

        $this->assertDatabaseHas('categories', ['name' => 'Eletrônicos']);
    }

    public function test_admin_can_create_category_with_parent_id(): void
    {
        $admin = $this->createAdmin();
        $parent = Category::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/categories', [
                'name' => 'Subcategoria Filha',
                'parent_id' => $parent->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('categories', [
            'name' => 'Subcategoria Filha',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_create_category_fails_without_name(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/categories', ['description' => 'Sem nome']);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['name']]);
    }

    // ── Update (admin) ────────────────────────────────────────────────────────

    public function test_guest_cannot_update_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->putJson("/api/v1/categories/{$category->id}", ['name' => 'Novo Nome']);

        $response->assertStatus(401);
    }

    public function test_customer_cannot_update_category(): void
    {
        $customer = $this->createCustomer();
        $category = Category::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/categories/{$category->id}", ['name' => 'Novo Nome']);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_category(): void
    {
        $admin = $this->createAdmin();
        $category = Category::factory()->create(['name' => 'Antigo Nome']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/categories/{$category->id}", ['name' => 'Novo Nome Atualizado']);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Novo Nome Atualizado');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Novo Nome Atualizado']);
    }

    public function test_admin_can_update_category_parent_id(): void
    {
        $admin = $this->createAdmin();
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => null]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/categories/{$child->id}", ['parent_id' => $parent->id]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => $parent->id,
        ]);
    }

    // ── Destroy (admin) ───────────────────────────────────────────────────────

    public function test_guest_cannot_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(401);
    }

    public function test_customer_cannot_delete_category(): void
    {
        $customer = $this->createCustomer();
        $category = Category::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_category(): void
    {
        $admin = $this->createAdmin();
        $category = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $category->id]);
        $product = Product::factory()->create(['category_id' => $child->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'active' => false]);
        $this->assertDatabaseHas('categories', ['id' => $child->id, 'active' => false]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => $child->id]);
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

    public function test_products_by_category_returns_404_for_inactive_category(): void
    {
        $category = Category::factory()->inactive()->create();

        $response = $this->getJson("/api/v1/categories/{$category->id}/products");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_products_by_category_returns_404_when_an_ancestor_is_inactive(): void
    {
        $inactiveParent = Category::factory()->inactive()->create();
        $category = Category::factory()->create(['parent_id' => $inactiveParent->id, 'active' => true]);

        $response = $this->getJson("/api/v1/categories/{$category->id}/products");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_products_by_category_returns_404_when_a_higher_ancestor_is_inactive(): void
    {
        $inactiveAncestor = Category::factory()->inactive()->create();
        $activeParent = Category::factory()->create(['parent_id' => $inactiveAncestor->id, 'active' => true]);
        $category = Category::factory()->create(['parent_id' => $activeParent->id, 'active' => true]);

        $response = $this->getJson("/api/v1/categories/{$category->id}/products");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
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

    public function test_products_by_category_include_only_active_deep_descendants(): void
    {
        $root = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $root->id]);
        $grandchild = Category::factory()->create(['parent_id' => $child->id]);
        $inactiveDescendant = Category::factory()->inactive()->create(['parent_id' => $child->id]);

        Product::factory()->create([
            'category_id' => $root->id,
            'name' => 'Root Product',
            'slug' => 'root-product',
            'active' => true,
        ]);
        Product::factory()->create([
            'category_id' => $grandchild->id,
            'name' => 'Deep Product',
            'slug' => 'deep-product',
            'active' => true,
        ]);
        Product::factory()->create([
            'category_id' => $inactiveDescendant->id,
            'name' => 'Hidden Product',
            'slug' => 'hidden-product',
            'active' => true,
        ]);

        $response = $this->getJson("/api/v1/categories/{$root->id}/products");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $this->assertSame(['Deep Product', 'Root Product'], collect($response->json('data'))->pluck('name')->sort()->values()->all());
    }

    public function test_products_by_category_excludes_products_from_active_descendants_inside_inactive_branch(): void
    {
        $root = Category::factory()->create(['active' => true]);
        $activeChild = Category::factory()->create(['parent_id' => $root->id, 'active' => true]);
        $inactiveChild = Category::factory()->inactive()->create(['parent_id' => $root->id]);
        $activeGrandchildOfInactiveChild = Category::factory()->create([
            'parent_id' => $inactiveChild->id,
            'active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $activeChild->id,
            'name' => 'Visible Product',
            'slug' => 'visible-product',
            'active' => true,
        ]);
        Product::factory()->create([
            'category_id' => $activeGrandchildOfInactiveChild->id,
            'name' => 'Hidden Branch Product',
            'slug' => 'hidden-branch-product',
            'active' => true,
        ]);

        $response = $this->getJson("/api/v1/categories/{$root->id}/products");

        $response->assertStatus(200);
        $this->assertSame(['Visible Product'], collect($response->json('data'))->pluck('name')->all());
    }
}
