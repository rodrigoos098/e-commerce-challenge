<?php

namespace Tests\Unit\Repositories;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CategoryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CategoryRepository();
    }

    // ── Tree ──────────────────────────────────────────────────────────────────

    public function test_tree_returns_only_root_categories(): void
    {
        $root = Category::factory()->create(['parent_id' => null]);
        Category::factory()->create(['parent_id' => $root->id]);

        $tree = $this->repository->tree();

        $this->assertCount(1, $tree);
        $this->assertNull($tree->first()->parent_id);
    }

    public function test_tree_loads_children(): void
    {
        $root = Category::factory()->create(['parent_id' => null]);
        $child = Category::factory()->create(['parent_id' => $root->id]);

        $tree = $this->repository->tree();

        $this->assertCount(1, $tree->first()->children);
        $this->assertEquals($child->id, $tree->first()->children->first()->id);
    }

    public function test_tree_excludes_inactive_root_categories(): void
    {
        Category::factory()->create(['active' => true]);
        Category::factory()->inactive()->create();

        $tree = $this->repository->tree();

        $this->assertCount(1, $tree);
        $this->assertTrue($tree->first()->active);
    }

    // ── All ───────────────────────────────────────────────────────────────────

    public function test_all_returns_all_categories_flat(): void
    {
        Category::factory()->count(3)->create();

        $all = $this->repository->all();

        $this->assertCount(3, $all);
    }

    public function test_all_orders_by_name(): void
    {
        Category::factory()->create(['name' => 'Roupas', 'slug' => 'roupas']);
        Category::factory()->create(['name' => 'Eletronicos', 'slug' => 'eletronicos']);

        $all = $this->repository->all();

        $this->assertEquals('Eletronicos', $all->first()->name);
    }

    // ── FindById ──────────────────────────────────────────────────────────────

    public function test_find_by_id_returns_category(): void
    {
        $category = Category::factory()->create();

        $found = $this->repository->findById($category->id);

        $this->assertNotNull($found);
        $this->assertEquals($category->id, $found->id);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $found = $this->repository->findById(9999);

        $this->assertNull($found);
    }

    // ── FindBySlug ────────────────────────────────────────────────────────────

    public function test_find_by_slug_returns_category(): void
    {
        Category::factory()->create(['slug' => 'eletronicos-de-casa']);

        $found = $this->repository->findBySlug('eletronicos-de-casa');

        $this->assertNotNull($found);
        $this->assertEquals('eletronicos-de-casa', $found->slug);
    }

    public function test_find_by_slug_returns_null_when_not_found(): void
    {
        $found = $this->repository->findBySlug('nao-existe');

        $this->assertNull($found);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_create_category(): void
    {
        $data = [
            'name' => 'Nova Categoria',
            'slug' => 'nova-categoria',
            'description' => 'Descrição',
            'active' => true,
        ];

        $category = $this->repository->create($data);

        $this->assertDatabaseHas('categories', ['name' => 'Nova Categoria']);
        $this->assertEquals('Nova Categoria', $category->name);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_update_category(): void
    {
        $category = Category::factory()->create(['name' => 'Antiga']);

        $updated = $this->repository->update($category, ['name' => 'Nova Nome']);

        $this->assertEquals('Nova Nome', $updated->name);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Nova Nome']);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_delete_category(): void
    {
        $category = Category::factory()->create();

        $result = $this->repository->delete($category);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    // ── SlugExists ────────────────────────────────────────────────────────────

    public function test_slug_exists_returns_true_for_existing_slug(): void
    {
        Category::factory()->create(['slug' => 'roupas']);

        $this->assertTrue($this->repository->slugExists('roupas'));
    }

    public function test_slug_exists_returns_false_for_non_existing_slug(): void
    {
        $this->assertFalse($this->repository->slugExists('nao-existe'));
    }

    public function test_slug_exists_excludes_given_id(): void
    {
        $category = Category::factory()->create(['slug' => 'roupas']);

        $this->assertFalse($this->repository->slugExists('roupas', $category->id));
    }

    // ── Hierarchia ────────────────────────────────────────────────────────────

    public function test_category_can_have_children(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->count(2)->create(['parent_id' => $parent->id]);

        $found = $this->repository->findById($parent->id);

        $this->assertCount(2, $found->children);
    }
}
