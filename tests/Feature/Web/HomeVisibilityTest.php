<?php

namespace Tests\Feature\Web;

use App\Models\Category;
use App\Models\Product;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_hides_products_from_inactive_root_categories_after_cache_invalidation(): void
    {
        $category = Category::factory()->create(['active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'active' => true,
            'quantity' => 10,
            'name' => 'Produto da Home',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('featured_products', fn ($products): bool => collect($products)
                    ->contains(fn (array $item): bool => $item['id'] === $product->id)));

        app(CategoryService::class)->update($category, ['active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('featured_products', fn ($products): bool => collect($products)
                    ->doesntContain(fn (array $item): bool => $item['id'] === $product->id)));
    }

    public function test_home_hides_products_from_active_subcategories_inside_inactive_branches(): void
    {
        $inactiveRoot = Category::factory()->inactive()->create();
        $activeChild = Category::factory()->create([
            'parent_id' => $inactiveRoot->id,
            'active' => true,
        ]);
        $product = Product::factory()->create([
            'category_id' => $activeChild->id,
            'active' => true,
            'quantity' => 10,
            'name' => 'Produto do Ramo Inativo',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('featured_products', fn ($products): bool => collect($products)
                    ->doesntContain(fn (array $item): bool => $item['id'] === $product->id)));
    }
}
