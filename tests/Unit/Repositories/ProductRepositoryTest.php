<?php

namespace Tests\Unit\Repositories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Repositories\ProductQueryBuilder;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProductRepository(new ProductQueryBuilder());
    }

    // ── Paginate ──────────────────────────────────────────────────────────────

    public function test_paginate_returns_paginated_results(): void
    {
        Product::factory()->count(5)->create();

        $result = $this->repository->paginate();

        $this->assertCount(5, $result->items());
        $this->assertEquals(5, $result->total());
    }

    public function test_paginate_with_search_filter_by_name(): void
    {
        Product::factory()->create(['name' => 'Camiseta Azul', 'slug' => 'camiseta-azul']);
        Product::factory()->create(['name' => 'Calça Preta', 'slug' => 'calca-preta']);

        $result = $this->repository->paginate(['search' => 'Camiseta']);

        $this->assertCount(1, $result->items());
        $this->assertEquals('Camiseta Azul', $result->items()[0]->name);
    }

    public function test_paginate_with_category_filter(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->create(); // different category

        $result = $this->repository->paginate(['category_id' => $category->id]);

        $this->assertCount(1, $result->items());
    }

    public function test_paginate_with_active_filter(): void
    {
        Product::factory()->create(['active' => true]);
        Product::factory()->inactive()->create();

        $result = $this->repository->paginate(['active' => true]);

        $this->assertCount(1, $result->items());
        $this->assertTrue($result->items()[0]->active);
    }

    public function test_paginate_with_in_stock_filter(): void
    {
        Product::factory()->create(['quantity' => 10]);
        Product::factory()->outOfStock()->create();

        $result = $this->repository->paginate(['in_stock' => true]);

        $this->assertCount(1, $result->items());
    }

    public function test_paginate_with_low_stock_filter(): void
    {
        Product::factory()->lowStock()->create();
        Product::factory()->create(['quantity' => 50, 'min_quantity' => 5]);

        $result = $this->repository->paginate(['low_stock' => true]);

        $this->assertCount(1, $result->items());
    }

    public function test_paginate_with_price_range_filter(): void
    {
        Product::factory()->create(['price' => 50.00, 'slug' => 'barato']);
        Product::factory()->create(['price' => 200.00, 'slug' => 'caro']);

        $result = $this->repository->paginate(['min_price' => 100, 'max_price' => 300]);

        $this->assertCount(1, $result->items());
        $this->assertEquals('200.00', $result->items()[0]->price);
    }

    public function test_paginate_respects_per_page(): void
    {
        Product::factory()->count(10)->create();

        $result = $this->repository->paginate([], 3);

        $this->assertCount(3, $result->items());
        $this->assertEquals(10, $result->total());
    }

    // ── FindById ──────────────────────────────────────────────────────────────

    public function test_find_by_id_returns_product(): void
    {
        $product = Product::factory()->create();

        $found = $this->repository->findById($product->id);

        $this->assertNotNull($found);
        $this->assertEquals($product->id, $found->id);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $found = $this->repository->findById(9999);

        $this->assertNull($found);
    }

    public function test_find_by_id_eager_loads_category_and_tags(): void
    {
        $product = Product::factory()->create();
        $tag = Tag::factory()->create();
        $product->tags()->attach($tag);

        $found = $this->repository->findById($product->id);

        $this->assertTrue($found->relationLoaded('category'));
        $this->assertTrue($found->relationLoaded('tags'));
    }

    // ── FindBySlug ────────────────────────────────────────────────────────────

    public function test_find_by_slug_returns_product(): void
    {
        $product = Product::factory()->create(['slug' => 'meu-produto-slug']);

        $found = $this->repository->findBySlug('meu-produto-slug');

        $this->assertNotNull($found);
        $this->assertEquals($product->id, $found->id);
    }

    public function test_find_by_slug_returns_null_when_not_found(): void
    {
        $found = $this->repository->findBySlug('nao-existe');

        $this->assertNull($found);
    }

    public function test_find_by_ids_for_update_returns_locked_products_in_stable_order(): void
    {
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();

        $products = $this->repository->findByIdsForUpdate([$productB->id, $productA->id]);

        $this->assertSame([$productA->id, $productB->id], $products->pluck('id')->all());
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_create_product(): void
    {
        $category = Category::factory()->create();
        $data = [
            'name' => 'Produto Novo',
            'slug' => 'produto-novo',
            'description' => 'Descrição',
            'price' => 99.90,
            'quantity' => 20,
            'min_quantity' => 5,
            'active' => true,
            'category_id' => $category->id,
        ];

        $product = $this->repository->create($data);

        $this->assertDatabaseHas('products', ['name' => 'Produto Novo']);
        $this->assertEquals('Produto Novo', $product->name);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_update_product(): void
    {
        $product = Product::factory()->create(['name' => 'Nome Antigo', 'price' => 50.00]);

        $updated = $this->repository->update($product, ['name' => 'Nome Novo', 'price' => 79.90]);

        $this->assertEquals('Nome Novo', $updated->name);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Nome Novo']);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_delete_soft_deletes_product(): void
    {
        $product = Product::factory()->create();

        $result = $this->repository->delete($product);

        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    // ── LowStock ─────────────────────────────────────────────────────────────

    public function test_low_stock_returns_active_low_stock_products(): void
    {
        Product::factory()->lowStock()->create(['active' => true]);
        Product::factory()->lowStock()->inactive()->create();
        Product::factory()->create(['quantity' => 50, 'min_quantity' => 5, 'active' => true]);

        $result = $this->repository->lowStock();

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->active);
    }

    // ── SyncTags ─────────────────────────────────────────────────────────────

    public function test_sync_tags_attaches_tags_to_product(): void
    {
        $product = Product::factory()->create();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $this->repository->syncTags($product, [$tag1->id, $tag2->id]);

        $this->assertCount(2, $product->tags()->get());
    }

    public function test_sync_tags_removes_old_tags(): void
    {
        $product = Product::factory()->create();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();
        $product->tags()->attach([$tag1->id, $tag2->id]);

        $this->repository->syncTags($product, [$tag1->id]);

        $this->assertCount(1, $product->tags()->get());
    }

    // ── SlugExists ────────────────────────────────────────────────────────────

    public function test_slug_exists_returns_true_for_existing_slug(): void
    {
        Product::factory()->create(['slug' => 'meu-slug']);

        $this->assertTrue($this->repository->slugExists('meu-slug'));
    }

    public function test_slug_exists_returns_false_for_non_existing_slug(): void
    {
        $this->assertFalse($this->repository->slugExists('nao-existe'));
    }

    public function test_slug_exists_excludes_given_id(): void
    {
        $product = Product::factory()->create(['slug' => 'meu-slug']);

        $this->assertFalse($this->repository->slugExists('meu-slug', $product->id));
    }
}
