<?php

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductCatalogFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_preserves_combined_filters_and_page_in_props(): void
    {
        $category = Category::factory()->create();
        $olderMatch = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Camiseta Linho Natural',
            'slug' => 'camiseta-linho-natural',
            'price' => 79.90,
            'created_at' => Carbon::parse('2026-01-01 10:00:00'),
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Camiseta Linho Premium',
            'slug' => 'camiseta-linho-premium',
            'price' => 89.90,
            'created_at' => Carbon::parse('2026-01-02 10:00:00'),
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Calca Linho Premium',
            'slug' => 'calca-linho-premium',
            'price' => 89.90,
        ]);

        $this->get("/products?search=Camiseta&category_id={$category->id}&price_min=50&price_max=100&page=2&per_page=1")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Index')
                ->where('filters.search', 'Camiseta')
                ->where('filters.category_id', (string) $category->id)
                ->where('filters.price_min', '50')
                ->where('filters.price_max', '100')
                ->where('filters.page', '2')
                ->where('products.meta.current_page', 2)
                ->has('products.data', 1)
                ->where('products.data.0.id', $olderMatch->id));
    }

    public function test_public_catalog_accepts_legacy_price_query_keys_and_reflects_canonical_filter_state(): void
    {
        $category = Category::factory()->create();
        $matchingProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Bolsa Couro Mini',
            'slug' => 'bolsa-couro-mini',
            'price' => 74.90,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Bolsa Couro Maxi',
            'slug' => 'bolsa-couro-maxi',
            'price' => 124.90,
        ]);

        $this->get('/products?min_price=60&max_price=80')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Index')
                ->where('filters.price_min', '60')
                ->where('filters.price_max', '80')
                ->has('products.data', 1)
                ->where('products.data.0.id', $matchingProduct->id));
    }

    public function test_public_catalog_hides_products_from_inactive_category_branches(): void
    {
        $inactiveRoot = Category::factory()->inactive()->create();
        $activeChild = Category::factory()->create([
            'parent_id' => $inactiveRoot->id,
            'active' => true,
        ]);
        $visibleCategory = Category::factory()->create();
        $hiddenProduct = Product::factory()->create([
            'category_id' => $activeChild->id,
            'name' => 'Produto Oculto',
            'slug' => 'produto-oculto',
            'active' => true,
        ]);
        $visibleProduct = Product::factory()->create([
            'category_id' => $visibleCategory->id,
            'name' => 'Produto Visivel',
            'slug' => 'produto-visivel',
            'active' => true,
        ]);

        $this->get('/products')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Index')
                ->where('products.data', fn ($products): bool => collect($products)
                    ->pluck('id')
                    ->contains($visibleProduct->id))
                ->where('products.data', fn ($products): bool => collect($products)
                    ->pluck('id')
                    ->doesntContain($hiddenProduct->id)));
    }
}
