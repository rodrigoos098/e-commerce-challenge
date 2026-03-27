<?php

namespace Tests\Feature\Api\V1;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    // ── Index ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_view_cart(): void
    {
        $response = $this->getJson('/api/v1/cart');

        $response->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_view_their_cart(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['id', 'items']]);
    }

    public function test_cart_api_returns_shipping_metadata_from_backend_rule(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        Address::factory()->for($user)->defaultShipping()->create([
            'zip_code' => '01310-100',
        ]);
        $product = Product::factory()->create([
            'price' => 80.0,
            'quantity' => 10,
            'active' => true,
        ]);
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.shipping_zip_code', '01310100')
            ->assertJsonPath('data.shipping_rule_label', 'Faixa de CEP 0-2')
            ->assertJsonPath('data.shipping_rule_description', 'Frete mockado aplicado para o CEP 01310100: faixa 0-2 com custo de R$ 14,90.')
            ->assertJsonPath('data.shipping_is_free', false)
            ->assertJsonPath('data.shipping_cost', 14.9)
            ->assertJsonPath('data.total', 102.9);
    }

    public function test_viewing_cart_creates_it_if_not_exists(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseMissing('carts', ['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/cart');

        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
    }

    // ── AddItem ───────────────────────────────────────────────────────────────

    public function test_guest_cannot_add_item_to_cart(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_add_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10, 'active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'data' => ['id', 'quantity', 'product']]);
    }

    public function test_cannot_add_item_exceeding_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 3, 'active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => 10,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_cannot_add_nonexistent_product(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => 9999,
                'quantity' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_adding_same_product_twice_accumulates_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 20, 'active' => true]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 3]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2]);

        $cart = Cart::where('user_id', $user->id)->first();
        $item = CartItem::where('cart_id', $cart->id)->where('product_id', $product->id)->first();

        $this->assertEquals(5, $item->quantity);
    }

    // ── UpdateItem ────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_update_cart_item_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 20, 'active' => true]);
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $item = CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/cart/items/{$item->id}", ['quantity' => 5]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quantity', 5);
    }

    public function test_user_cannot_update_another_users_cart_item(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10]);
        $cart = Cart::factory()->create(['user_id' => $user1->id]);
        $item = CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

        $response = $this->actingAs($user2, 'sanctum')
            ->putJson("/api/v1/cart/items/{$item->id}", ['quantity' => 2]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_cannot_update_cart_item_when_product_is_inactive(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 20, 'active' => false]);
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $item = CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/cart/items/{$item->id}", ['quantity' => 5]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['quantity']]);
    }

    public function test_cannot_update_cart_item_when_product_no_longer_exists(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 20, 'active' => true]);
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $item = CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        $product->delete();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/cart/items/{$item->id}", ['quantity' => 5]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['quantity']]);
    }

    // ── RemoveItem ────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_remove_cart_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $item = CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/cart/items/{$item->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_user_cannot_remove_another_users_cart_item(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user1->id]);
        $item = CartItem::factory()->create(['cart_id' => $cart->id]);

        $response = $this->actingAs($user2, 'sanctum')
            ->deleteJson("/api/v1/cart/items/{$item->id}");

        $response->assertStatus(404);
    }

    // ── Clear ─────────────────────────────────────────────────────────────────

    public function test_guest_cannot_clear_cart(): void
    {
        $response = $this->deleteJson('/api/v1/cart');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_clear_cart(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        // Each item needs a different product (unique constraint: cart_id + product_id)
        CartItem::factory()->count(3)->create(['cart_id' => $cart->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
    }

    public function test_database_allows_only_one_cart_per_user(): void
    {
        $user = User::factory()->create();

        Cart::factory()->create(['user_id' => $user->id]);

        $this->expectException(QueryException::class);

        Cart::factory()->create(['user_id' => $user->id]);
    }
}
