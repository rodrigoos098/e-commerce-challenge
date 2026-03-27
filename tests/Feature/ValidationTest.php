<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
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

        /** @var User $admin */
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function createCustomer(): User
    {
        /** @var User $customer */
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        return $customer;
    }

    private function seedCartForWebCheckout(User $customer): void
    {
        $product = Product::factory()->create([
            'price' => 75.0,
            'quantity' => 10,
            'active' => true,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $customer->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validWebCheckoutPayload(array $overrides = []): array
    {
        return array_merge([
            'shipping_mode' => 'new',
            'shipping_name' => 'Cliente Teste',
            'shipping_street' => 'Rua Alfa, 10',
            'shipping_city' => 'Sao Paulo',
            'shipping_state' => 'SP',
            'shipping_zip' => '01000-000',
            'shipping_country' => 'BR',
            'same_billing' => true,
            'notes' => 'Sem observacoes adicionais.',
            'payment_simulated' => true,
        ], $overrides);
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

    public function test_unique_slug_rule_allows_product_to_keep_its_own_slug_on_update(): void
    {
        // Regression: UniqueSlug($exceptId) must exclude the product being updated.
        // Without $exceptId, updating a product would always 422 when slug is unchanged.
        $product = Product::factory()->create(['slug' => 'slug-existente']);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", ['slug' => 'slug-existente'])
            ->assertStatus(200);
    }

    public function test_unique_slug_rule_rejects_update_with_slug_of_another_product(): void
    {
        // Regression: $exceptId only excludes the current product — another product's slug
        // must still be rejected.
        Product::factory()->create(['slug' => 'slug-do-outro']);
        $product = Product::factory()->create(['slug' => 'slug-atual']);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", ['slug' => 'slug-do-outro'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['slug']]);
    }

    // ── SufficientStock rule ──────────────────────────────────────────────────

    public function test_sufficient_stock_rule_rejects_quantity_exceeding_stock(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 3, 'active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 5])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quantity']]);
    }

    public function test_sufficient_stock_rule_accepts_quantity_within_stock(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10, 'active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 5])
            ->assertStatus(201);
    }

    public function test_cart_item_quantity_must_be_at_least_one(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10, 'active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 0])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quantity']]);
    }

    public function test_cart_item_requires_existing_product(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => 9999, 'quantity' => 1])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['product_id']]);
    }

    // ── Order address validation ───────────────────────────────────────────────

    public function test_order_requires_shipping_address(): void
    {
        /** @var User $user */
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
        /** @var User $user */
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

    public function test_web_checkout_requires_saved_shipping_address_selection(): void
    {
        $customer = $this->createCustomer();
        $this->seedCartForWebCheckout($customer);

        $this->actingAs($customer)
            ->post('/customer/orders', $this->validWebCheckoutPayload([
                'shipping_mode' => 'saved',
                'shipping_name' => null,
                'shipping_street' => null,
                'shipping_city' => null,
                'shipping_state' => null,
                'shipping_zip' => null,
                'shipping_country' => null,
            ]))
            ->assertSessionHasErrors(['shipping_address_id']);
    }

    public function test_web_checkout_requires_manual_billing_fields_when_same_billing_is_false(): void
    {
        $customer = $this->createCustomer();
        $this->seedCartForWebCheckout($customer);

        $this->actingAs($customer)
            ->post('/customer/orders', $this->validWebCheckoutPayload([
                'same_billing' => false,
                'billing_mode' => 'new',
            ]))
            ->assertSessionHasErrors([
                'billing_name',
                'billing_street',
                'billing_city',
                'billing_state',
                'billing_zip',
                'billing_country',
            ]);
    }

    public function test_web_checkout_requires_same_billing_and_string_notes(): void
    {
        $customer = $this->createCustomer();
        $this->seedCartForWebCheckout($customer);

        $payload = $this->validWebCheckoutPayload();
        unset($payload['same_billing']);
        $payload['notes'] = ['invalido'];

        $this->actingAs($customer)
            ->post('/customer/orders', $payload)
            ->assertSessionHasErrors([
                'same_billing',
                'notes',
            ]);
    }

    public function test_web_checkout_returns_specific_stock_error_when_cart_quantity_exceeds_available_stock(): void
    {
        $customer = $this->createCustomer();

        $product = Product::factory()->create([
            'name' => 'Notebook Pro',
            'price' => 75.0,
            'quantity' => 1,
            'active' => true,
        ]);

        $cart = Cart::factory()->create([
            'user_id' => $customer->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($customer)
            ->from('/customer/checkout')
            ->post('/customer/orders', $this->validWebCheckoutPayload())
            ->assertRedirect('/customer/checkout')
            ->assertSessionHasErrors([
                'cart' => 'O produto "Notebook Pro" possui apenas 1 unidade(s) disponivel(is). Ajuste o carrinho e tente novamente.',
            ]);
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
