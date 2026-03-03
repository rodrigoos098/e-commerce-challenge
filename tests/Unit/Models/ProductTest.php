<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    // ── Fillable ──────────────────────────────────────────────────────────────

    public function test_fillable_attributes_are_correct(): void
    {
        $expected = [
            'name', 'slug', 'description', 'price', 'cost_price',
            'quantity', 'min_quantity', 'active', 'category_id',
        ];

        $product = new Product;

        $this->assertEquals($expected, $product->getFillable());
    }

    // ── Casts ─────────────────────────────────────────────────────────────────

    public function test_price_is_cast_to_decimal(): void
    {
        $product = Product::factory()->create(['price' => '19.9']);

        $this->assertIsString($product->price);
        $this->assertEquals('19.90', $product->price);
    }

    public function test_cost_price_is_cast_to_decimal(): void
    {
        $product = Product::factory()->create(['cost_price' => '9.5']);

        $this->assertIsString($product->cost_price);
        $this->assertEquals('9.50', $product->cost_price);
    }

    public function test_quantity_is_cast_to_integer(): void
    {
        $product = Product::factory()->create(['quantity' => '50']);

        $this->assertIsInt($product->quantity);
        $this->assertEquals(50, $product->quantity);
    }

    public function test_min_quantity_is_cast_to_integer(): void
    {
        $product = Product::factory()->create(['min_quantity' => '10']);

        $this->assertIsInt($product->min_quantity);
        $this->assertEquals(10, $product->min_quantity);
    }

    public function test_active_is_cast_to_boolean(): void
    {
        $product = Product::factory()->create(['active' => 1]);

        $this->assertIsBool($product->active);
        $this->assertTrue($product->active);
    }

    // ── Slug auto-gerado ──────────────────────────────────────────────────────

    public function test_slug_is_auto_generated_from_name_on_create(): void
    {
        $product = Product::factory()->create([
            'name' => 'Produto Teste Incrível',
            'slug' => '',
        ]);

        $this->assertEquals('produto-teste-incrivel', $product->slug);
    }

    public function test_existing_slug_is_not_overwritten_on_create(): void
    {
        $product = Product::factory()->create([
            'name' => 'Produto Teste',
            'slug' => 'meu-slug-customizado',
        ]);

        $this->assertEquals('meu-slug-customizado', $product->slug);
    }

    public function test_slug_is_updated_when_name_changes(): void
    {
        $product = Product::factory()->create(['name' => 'Produto Original']);

        $product->update(['name' => 'Produto Alterado']);

        $this->assertEquals('produto-alterado', $product->slug);
    }

    public function test_slug_is_not_updated_when_name_does_not_change(): void
    {
        $product = Product::factory()->create(['name' => 'Produto Original']);
        $originalSlug = $product->slug;

        $product->update(['description' => 'Nova descrição']);

        $this->assertEquals($originalSlug, $product->fresh()->slug);
    }

    // ── Relacionamentos ───────────────────────────────────────────────────────

    public function test_category_relationship_returns_belongs_to(): void
    {
        $product = new Product;

        $this->assertInstanceOf(BelongsTo::class, $product->category());
    }

    public function test_category_relationship_is_category_model(): void
    {
        $product = Product::factory()->create();

        $this->assertInstanceOf(Category::class, $product->category);
    }

    public function test_tags_relationship_returns_belongs_to_many(): void
    {
        $product = new Product;

        $this->assertInstanceOf(BelongsToMany::class, $product->tags());
    }

    public function test_tags_relationship_is_tag_model(): void
    {
        $product = Product::factory()->create();
        $tag = Tag::factory()->create();
        $product->tags()->attach($tag);

        $this->assertInstanceOf(Tag::class, $product->tags->first());
    }

    public function test_order_items_relationship_returns_has_many(): void
    {
        $product = new Product;

        $this->assertInstanceOf(HasMany::class, $product->orderItems());
    }

    public function test_stock_movements_relationship_returns_has_many(): void
    {
        $product = new Product;

        $this->assertInstanceOf(HasMany::class, $product->stockMovements());
    }

    public function test_stock_movements_relationship_is_stock_movement_model(): void
    {
        $product = Product::factory()->create();
        StockMovement::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(StockMovement::class, $product->stockMovements->first());
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function test_scope_active_returns_only_active_products(): void
    {
        Product::factory()->create(['active' => true]);
        Product::factory()->inactive()->create();

        $active = Product::query()->active()->get();

        $this->assertCount(1, $active);
        $this->assertTrue($active->first()->active);
    }

    public function test_scope_in_stock_returns_only_products_with_quantity_greater_than_zero(): void
    {
        Product::factory()->create(['quantity' => 10]);
        Product::factory()->outOfStock()->create();

        $inStock = Product::query()->inStock()->get();

        $this->assertCount(1, $inStock);
        $this->assertGreaterThan(0, $inStock->first()->quantity);
    }

    public function test_scope_low_stock_returns_products_where_quantity_lte_min_quantity(): void
    {
        Product::factory()->lowStock()->create();   // quantity=2, min_quantity=10
        Product::factory()->create(['quantity' => 50, 'min_quantity' => 10]);

        $lowStock = Product::query()->lowStock()->get();

        $this->assertCount(1, $lowStock);
        $this->assertLessThanOrEqual($lowStock->first()->min_quantity, $lowStock->first()->quantity);
    }

    // ── Soft Delete ───────────────────────────────────────────────────────────

    public function test_product_uses_soft_deletes(): void
    {
        $product = Product::factory()->create();

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_soft_deleted_product_is_not_in_default_query(): void
    {
        $product = Product::factory()->create();
        $product->delete();

        $found = Product::find($product->id);

        $this->assertNull($found);
    }

    public function test_soft_deleted_product_can_be_found_with_trashed(): void
    {
        $product = Product::factory()->create();
        $product->delete();

        $found = Product::withTrashed()->find($product->id);

        $this->assertNotNull($found);
    }
}
