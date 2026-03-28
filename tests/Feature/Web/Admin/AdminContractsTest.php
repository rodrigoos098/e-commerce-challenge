<?php

namespace Tests\Feature\Web\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $movement = StockMovement::factory()->create([
            'product_id' => $product->id,
            'type' => 'ajuste',
            'quantity' => 4,
            'reason' => 'Contagem fisica no inventario',
        ]);

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
                ->has('movements', 1)
                ->where('movements.0.id', $movement->id)
                ->where('movements.0.type', 'ajuste')
                ->where('movements.0.notes', 'Contagem fisica no inventario'));
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

    public function test_admin_products_index_preserves_filters_when_paginating_and_returns_the_correct_page(): void
    {
        $admin = $this->createAdmin();
        $category = Category::factory()->create();
        $newestMatch = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Produto Premium Novo',
            'active' => true,
            'created_at' => Carbon::parse('2026-01-02 10:00:00'),
        ]);
        $olderMatch = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Produto Premium Antigo',
            'active' => true,
            'created_at' => Carbon::parse('2026-01-01 10:00:00'),
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Produto Inativo',
            'active' => false,
        ]);

        $this->actingAs($admin)
            ->get("/admin/products?search=Premium&category_id={$category->id}&active=1&per_page=1&page=1")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Products/Index')
                ->where('products.meta.current_page', 1)
                ->where('products.data.0.id', $newestMatch->id));

        $this->actingAs($admin)
            ->get("/admin/products?search=Premium&category_id={$category->id}&active=1&per_page=1&page=2")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Products/Index')
                ->where('filters.search', 'Premium')
                ->where('filters.category_id', (string) $category->id)
                ->where('filters.active', '1')
                ->where('products.meta.current_page', 2)
                ->where('products.data.0.id', $olderMatch->id));
    }

    public function test_admin_can_clear_product_cost_price_explicitly(): void
    {
        $admin = $this->createAdmin();
        $product = Product::factory()->create(['cost_price' => 149.90]);

        $this->actingAs($admin)
            ->put("/admin/products/{$product->id}", [
                'cost_price' => null,
            ])
            ->assertRedirect('/admin/products')
            ->assertSessionHas('success', 'Produto atualizado com sucesso!');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'cost_price' => null,
        ]);
    }

    public function test_admin_can_upload_product_image_when_creating_a_product(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->post('/admin/products', [
                'name' => 'Produto com Imagem',
                'description' => 'Descricao de teste',
                'price' => 199.90,
                'cost_price' => 120.00,
                'quantity' => 5,
                'min_quantity' => 1,
                'category_id' => $category->id,
                'active' => true,
                'image' => UploadedFile::fake()->create('produto.jpg', 128, 'image/jpeg'),
            ])
            ->assertRedirect('/admin/products')
            ->assertSessionHas('success', 'Produto criado com sucesso!');

        $product = Product::query()->where('name', 'Produto com Imagem')->firstOrFail();

        $this->assertNotNull($product->image_url);
        $this->assertStringStartsWith('/storage/products/', $product->image_url);
        $this->assertTrue(Storage::disk('public')->exists(str_replace('/storage/', '', $product->image_url)));
    }

    public function test_admin_product_update_requires_stock_adjustment_reason_when_quantity_changes(): void
    {
        $admin = $this->createAdmin();
        $product = Product::factory()->create(['quantity' => 10]);

        $this->actingAs($admin)
            ->from("/admin/products/{$product->id}/edit")
            ->put("/admin/products/{$product->id}", [
                'quantity' => 15,
            ])
            ->assertRedirect("/admin/products/{$product->id}/edit")
            ->assertSessionHasErrors([
                'stock_adjustment_reason' => 'Informe o motivo do ajuste de estoque.',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 10,
        ]);
    }

    public function test_admin_tags_index_exposes_tags_with_product_counts(): void
    {
        $admin = $this->createAdmin();
        $tag = Tag::factory()->create([
            'name' => 'Lancamento',
            'slug' => 'lancamento',
        ]);
        Product::factory()->count(2)->create()->each(fn (Product $product) => $tag->products()->attach($product));

        $this->actingAs($admin)
            ->get('/admin/tags')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Tags/Index')
                ->has('tags', 1)
                ->where('tags.0.name', 'Lancamento')
                ->where('tags.0.products_count', 2));
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

    public function test_admin_order_show_exposes_mock_payment_fields_without_overwriting_logistics_status(): void
    {
        $admin = $this->createAdmin();
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $order = Order::factory()->for($customer)->create([
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_method' => Order::MOCK_PAYMENT_METHOD,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Show')
                ->where('order.id', $order->id)
                ->where('order.status', 'processing')
                ->where('order.payment_status', 'paid')
                ->where('order.payment_method', Order::MOCK_PAYMENT_METHOD)
                ->where('order.paid_at', $order->paid_at?->toIso8601String()));
    }
}
