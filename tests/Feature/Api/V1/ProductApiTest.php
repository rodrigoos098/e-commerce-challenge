<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductApiTest extends TestCase
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

    // ── Index (public) ────────────────────────────────────────────────────────

    public function test_guest_can_list_products(): void
    {
        Product::factory()->count(3)->create(['active' => true]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data', 'meta', 'links']);
    }

    public function test_products_are_paginated(): void
    {
        Product::factory()->count(20)->create(['active' => true]);

        $response = $this->getJson('/api/v1/products?per_page=5');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(20, $response->json('meta.total'));
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->create(); // different category

        $response = $this->getJson("/api/v1/products?category_id={$category->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_products_can_be_searched(): void
    {
        Product::factory()->create(['name' => 'Smartphone Samsung', 'slug' => 'smartphone-samsung']);
        Product::factory()->create(['name' => 'Televisão LG', 'slug' => 'televisao-lg']);

        $response = $this->getJson('/api/v1/products?search=Smartphone');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Smartphone Samsung', $response->json('data.0.name'));
    }

    // ── Show (public) ─────────────────────────────────────────────────────────

    public function test_guest_can_view_single_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/v1/products/9999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message']);
    }

    // ── Store (admin) ─────────────────────────────────────────────────────────

    public function test_guest_cannot_create_product(): void
    {
        $response = $this->postJson('/api/v1/products', []);

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_customer_cannot_create_product(): void
    {
        $customer = $this->createCustomer();
        $category = Category::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/products', [
                'name' => 'Produto Teste',
                'description' => 'Descrição',
                'price' => 99.9,
                'quantity' => 10,
                'category_id' => $category->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_product(): void
    {
        $admin = $this->createAdmin();
        $category = Category::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/products', [
                'name' => 'Produto Admin',
                'description' => 'Descrição do produto',
                'price' => 149.90,
                'quantity' => 50,
                'min_quantity' => 5,
                'category_id' => $category->id,
                'active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'slug', 'price']]);

        $this->assertDatabaseHas('products', ['name' => 'Produto Admin']);
    }

    public function test_create_product_fails_without_required_fields(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/products', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'errors' => ['name', 'description', 'price', 'quantity', 'category_id']]);
    }

    public function test_admin_can_create_product_with_tags(): void
    {
        $admin = $this->createAdmin();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/products', [
                'name' => 'Produto com Tags',
                'description' => 'Descrição',
                'price' => 50.0,
                'quantity' => 10,
                'category_id' => $category->id,
                'tag_ids' => [$tag->id],
            ]);

        $response->assertStatus(201);
        $product = Product::where('name', 'Produto com Tags')->first();
        $this->assertCount(1, $product->tags);
    }

    // ── Update (admin) ────────────────────────────────────────────────────────

    public function test_guest_cannot_update_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->putJson("/api/v1/products/{$product->id}", ['name' => 'Novo']);

        $response->assertStatus(401);
    }

    public function test_admin_can_update_product(): void
    {
        $admin = $this->createAdmin();
        $product = Product::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", [
                'name' => 'Nome Atualizado',
                'price' => 199.90,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nome Atualizado');
    }

    // ── Destroy (admin) ───────────────────────────────────────────────────────

    public function test_guest_cannot_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(401);
    }

    public function test_admin_can_delete_product(): void
    {
        $admin = $this->createAdmin();
        $product = Product::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    // ── LowStock (admin) ──────────────────────────────────────────────────────

    public function test_guest_cannot_view_low_stock(): void
    {
        $response = $this->getJson('/api/v1/products/low-stock');

        $response->assertStatus(401);
    }

    public function test_admin_can_view_low_stock_products(): void
    {
        $admin = $this->createAdmin();
        Product::factory()->lowStock()->create(['active' => true]);
        Product::factory()->create(['quantity' => 50, 'min_quantity' => 5, 'active' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/products/low-stock');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data']);

        $this->assertCount(1, $response->json('data'));
    }
}
