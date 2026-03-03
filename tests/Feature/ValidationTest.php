<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function validProductData(?Category $category = null): array
    {
        $category ??= Category::factory()->create();

        return [
            'name' => 'Produto Válido '.uniqid(),
            'description' => 'Uma descrição válida do produto.',
            'price' => 99.90,
            'quantity' => 10,
            'category_id' => $category->id,
        ];
    }

    // ── Register validation ───────────────────────────────────────────────────

    public function test_register_requires_name(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_register_requires_valid_email(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test',
            'email' => 'not-an-email',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'DifferentPassword',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['password']]);
    }

    // ── Product validation ────────────────────────────────────────────────────

    public function test_create_product_requires_name(): void
    {
        $data = $this->validProductData();
        unset($data['name']);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products', $data)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_create_product_requires_description(): void
    {
        $data = $this->validProductData();
        unset($data['description']);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products', $data)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['description']]);
    }

    public function test_create_product_requires_positive_price(): void
    {
        $data = array_merge($this->validProductData(), ['price' => -10]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products', $data)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['price']]);
    }

    public function test_create_product_requires_price_greater_than_zero(): void
    {
        $data = array_merge($this->validProductData(), ['price' => 0]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products', $data)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['price']]);
    }

    public function test_create_product_requires_existing_category(): void
    {
        $data = array_merge($this->validProductData(), ['category_id' => 9999]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products', $data)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['category_id']]);
    }

    public function test_create_product_rejects_duplicate_name(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['name' => 'Produto Existente', 'category_id' => $category->id]);

        $data = array_merge($this->validProductData($category), ['name' => 'Produto Existente']);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products', $data)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_cost_price_must_be_less_than_price(): void
    {
        $data = array_merge($this->validProductData(), [
            'price' => 50.0,
            'cost_price' => 100.0,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products', $data)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['cost_price']]);
    }

    public function test_tag_ids_must_exist(): void
    {
        $data = array_merge($this->validProductData(), ['tag_ids' => [9999]]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products', $data)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['tag_ids.0']]);
    }

    // ── UniqueSlug rule ───────────────────────────────────────────────────────

    public function test_unique_slug_rule_rejects_duplicate_slug(): void
    {
        Product::factory()->create(['name' => 'Eletronico', 'slug' => 'eletronico']);

        $data = array_merge($this->validProductData(), ['slug' => 'eletronico']);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products', $data)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['slug']]);
    }

    public function test_unique_slug_rule_rejects_slug_of_soft_deleted_product(): void
    {
        // UniqueSlug uses withTrashed(), so soft-deleted product slugs must also be blocked.
        $product = Product::factory()->create(['slug' => 'slug-deletado']);
        $product->delete();

        $data = array_merge($this->validProductData(), ['slug' => 'slug-deletado']);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products', $data)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['slug']]);
    }

    public function test_unique_slug_rule_accepts_unique_slug(): void
    {
        $data = array_merge($this->validProductData(), ['slug' => 'meu-slug-unico']);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products', $data)
            ->assertStatus(201);
    }

    // ── SufficientStock rule ──────────────────────────────────────────────────

    public function test_sufficient_stock_rule_rejects_quantity_exceeding_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 3, 'active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 5])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quantity']]);
    }

    public function test_sufficient_stock_rule_accepts_quantity_within_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10, 'active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 5])
            ->assertStatus(201);
    }

    public function test_cart_item_quantity_must_be_at_least_one(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10, 'active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 0])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quantity']]);
    }

    public function test_cart_item_requires_existing_product(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => 9999, 'quantity' => 1])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['product_id']]);
    }

    // ── Order address validation ───────────────────────────────────────────────

    public function test_order_requires_shipping_address(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', ['billing_address' => [
                'street' => '1 St', 'city' => 'City', 'state' => 'SP', 'zip_code' => '00000', 'country' => 'BR',
            ]])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['shipping_address']]);
    }

    public function test_order_requires_shipping_address_street(): void
    {
        $user = User::factory()->create();
        $address = ['city' => 'SP', 'state' => 'SP', 'zip_code' => '00000', 'country' => 'BR'];

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/orders', [
                'shipping_address' => $address,
                'billing_address' => $address,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['shipping_address.street']]);
    }

    public function test_order_update_status_validates_status_value(): void
    {
        $order = \App\Models\Order::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'not-a-valid-status'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['status']]);
    }

    // ── ValidParentCategory rule ───────────────────────────────────────────────────

    public function test_valid_parent_category_rule_rejects_nonexistent_parent(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/categories', [
                'name' => 'Nova Categoria',
                'parent_id' => 9999,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['parent_id']]);
    }

    public function test_valid_parent_category_rule_accepts_null_parent(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/categories', [
                'name' => 'Categoria Raiz '.uniqid(),
                'parent_id' => null,
            ])
            ->assertStatus(201);
    }

    public function test_valid_parent_category_rule_accepts_valid_parent(): void
    {
        $parent = Category::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/categories', [
                'name' => 'Categoria Filha '.uniqid(),
                'parent_id' => $parent->id,
            ])
            ->assertStatus(201);
    }

    public function test_valid_parent_category_rule_rejects_self_as_parent(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/categories/{$category->id}", [
                'parent_id' => $category->id,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['parent_id']]);
    }

    public function test_valid_parent_category_rule_rejects_circular_reference(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        // Setting $parent's parent to $child would create a circle
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/categories/{$parent->id}", [
                'parent_id' => $child->id,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['parent_id']]);
    }
}
