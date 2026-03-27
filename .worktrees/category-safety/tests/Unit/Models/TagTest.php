<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    // ── Fillable ──────────────────────────────────────────────────────────────

    public function test_fillable_attributes_are_correct(): void
    {
        $expected = ['name', 'slug'];

        $tag = new Tag();

        $this->assertEquals($expected, $tag->getFillable());
    }

    // ── Slug auto-gerado ──────────────────────────────────────────────────────

    public function test_slug_is_auto_generated_from_name_on_create(): void
    {
        $tag = Tag::factory()->create([
            'name' => 'Promoção Especial',
            'slug' => '',
        ]);

        $this->assertEquals('promocao-especial', $tag->slug);
    }

    public function test_existing_slug_is_not_overwritten_on_create(): void
    {
        $tag = Tag::factory()->create([
            'name' => 'Oferta',
            'slug' => 'oferta-do-dia',
        ]);

        $this->assertEquals('oferta-do-dia', $tag->slug);
    }

    // ── Relacionamentos ───────────────────────────────────────────────────────

    public function test_products_relationship_returns_belongs_to_many(): void
    {
        $tag = new Tag();

        $this->assertInstanceOf(BelongsToMany::class, $tag->products());
    }

    public function test_products_relationship_returns_products(): void
    {
        $tag = Tag::factory()->create();
        $product = Product::factory()->create();
        $tag->products()->attach($product);

        $this->assertCount(1, $tag->products);
        $this->assertInstanceOf(Product::class, $tag->products->first());
    }

    public function test_tag_can_be_attached_to_multiple_products(): void
    {
        $tag = Tag::factory()->create();
        $products = Product::factory()->count(3)->create();
        $tag->products()->attach($products->pluck('id'));

        $this->assertCount(3, $tag->products);
    }

    public function test_product_can_have_multiple_tags(): void
    {
        $product = Product::factory()->create();
        $tags = Tag::factory()->count(2)->create();
        $product->tags()->attach($tags->pluck('id'));

        $this->assertCount(2, $product->tags);
    }
}
