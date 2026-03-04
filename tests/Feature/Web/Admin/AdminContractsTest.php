<?php

namespace Tests\Feature\Web\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminContractsTest extends TestCase
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

    public function test_admin_product_show_includes_loaded_category_and_tags(): void
    {
        $admin = $this->createAdmin();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $tags = Tag::factory()->count(2)->create();

        $product->tags()->attach($tags->pluck('id'));

        $this->actingAs($admin)
            ->get("/admin/products/{$product->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Products/Show')
                ->where('product.id', $product->id)
                ->where('product.category.id', $category->id)
                ->where('product.category.name', $category->name)
                ->has('product.tags', 2)
                ->where('product.tags', fn ($productTags): bool => collect($productTags)
                    ->pluck('id')
                    ->sort()
                    ->values()
                    ->all() === $tags->pluck('id')->sort()->values()->all())
                ->has('movements'));
    }

    public function test_admin_product_edit_includes_loaded_category_and_tags(): void
    {
        $admin = $this->createAdmin();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $tag = Tag::factory()->create();

        $product->tags()->attach($tag->id);

        $this->actingAs($admin)
            ->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Products/Edit')
                ->where('product.id', $product->id)
                ->where('product.category.id', $category->id)
                ->has('product.tags', 1)
                ->where('product.tags.0.id', $tag->id)
                ->has('categories')
                ->has('tags'));
    }

    public function test_admin_order_show_includes_structured_addresses(): void
    {
        $admin = $this->createAdmin();
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $order = Order::factory()->for($customer)->create([
            'shipping_address' => [
                'name' => 'Cliente Teste',
                'street' => 'Rua das Flores, 123',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'zip_code' => '01310-100',
                'country' => 'BR',
            ],
            'billing_address' => [
                'name' => 'Financeiro Teste',
                'street' => 'Avenida Central, 456',
                'city' => 'Campinas',
                'state' => 'SP',
                'zip_code' => '13010-000',
                'country' => 'BR',
            ],
        ]);

        $this->actingAs($admin)
            ->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Show')
                ->where('order.id', $order->id)
                ->where('order.shipping_address.street', 'Rua das Flores, 123')
                ->where('order.shipping_address.zip_code', '01310-100')
                ->where('order.billing_address.street', 'Avenida Central, 456')
                ->where('order.billing_address.zip_code', '13010-000'));
    }
}
