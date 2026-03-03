<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    // ── Fillable ──────────────────────────────────────────────────────────────

    public function test_fillable_attributes_are_correct(): void
    {
        $expected = ['name', 'slug', 'description', 'parent_id', 'active'];

        $category = new Category;

        $this->assertEquals($expected, $category->getFillable());
    }

    // ── Casts ─────────────────────────────────────────────────────────────────

    public function test_active_is_cast_to_boolean(): void
    {
        $category = Category::factory()->create(['active' => 1]);

        $this->assertIsBool($category->active);
        $this->assertTrue($category->active);
    }

    // ── Slug auto-gerado ──────────────────────────────────────────────────────

    public function test_slug_is_auto_generated_from_name_on_create(): void
    {
        $category = Category::factory()->create([
            'name' => 'Eletrônicos de Casa',
            'slug' => '',
        ]);

        $this->assertEquals('eletronicos-de-casa', $category->slug);
    }

    public function test_existing_slug_is_not_overwritten_on_create(): void
    {
        $category = Category::factory()->create([
            'name' => 'Roupas',
            'slug' => 'roupas-importadas',
        ]);

        $this->assertEquals('roupas-importadas', $category->slug);
    }

    public function test_slug_is_updated_when_name_changes(): void
    {
        $category = Category::factory()->create(['name' => 'Calçados']);

        $category->update(['name' => 'Calçados Esportivos']);

        $this->assertEquals('calcados-esportivos', $category->slug);
    }

    // ── Relacionamentos ───────────────────────────────────────────────────────

    public function test_parent_relationship_returns_belongs_to(): void
    {
        $category = new Category;

        $this->assertInstanceOf(BelongsTo::class, $category->parent());
    }

    public function test_parent_relationship_returns_parent_category(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertInstanceOf(Category::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    public function test_children_relationship_returns_has_many(): void
    {
        $category = new Category;

        $this->assertInstanceOf(HasMany::class, $category->children());
    }

    public function test_children_relationship_returns_child_categories(): void
    {
        $parent = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $parent->id]);
        $child2 = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertCount(2, $parent->children);
        $this->assertInstanceOf(Category::class, $parent->children->first());
    }

    public function test_products_relationship_returns_has_many(): void
    {
        $category = new Category;

        $this->assertInstanceOf(HasMany::class, $category->products());
    }

    public function test_products_relationship_returns_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $this->assertCount(3, $category->products);
        $this->assertInstanceOf(Product::class, $category->products->first());
    }

    public function test_root_category_has_no_parent(): void
    {
        $category = Category::factory()->create(['parent_id' => null]);

        $this->assertNull($category->parent);
    }
}
