<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CartFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');
    }

    public function test_full_cart_management_flow(): void
    {
        $product = Product::factory()->create(['quantity' => 20, 'active' => true, 'price' => 100.0]);

        // 1. View empty cart (auto-creates cart)
        $viewResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/cart');

        $viewResponse->assertStatus(200)
            ->assertJsonPath('data.items', []);

        // 2. Add item to cart
        $addResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => 3,
            ]);

        $addResponse->assertStatus(201);
        $itemId = $addResponse->json('data.id');

        // 3. Verify cart has the item
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/cart')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items');

        // 4. Update item quantity
        $updateResponse = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/cart/items/{$itemId}", ['quantity' => 5]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.quantity', 5);

        // 5. Add another item (different product)
        $product2 = Product::factory()->create(['quantity' => 10, 'active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product2->id,
                'quantity' => 2,
            ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/cart')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.items');

        // 6. Remove first item
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/cart/items/{$itemId}")
            ->assertStatus(200);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/cart')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items');

        // 7. Clear entire cart
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/cart')
            ->assertStatus(200);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/cart')
            ->assertStatus(200)
            ->assertJsonPath('data.items', []);
    }

    public function test_adding_same_product_increments_quantity(): void
    {
        $product = Product::factory()->create(['quantity' => 20, 'active' => true]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 3]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 4]);

        $cart = Cart::where('user_id', $this->user->id)->first();
        $item = CartItem::where('cart_id', $cart->id)->where('product_id', $product->id)->first();

        $this->assertEquals(7, $item->quantity);
    }

    public function test_cannot_add_more_quantity_than_available_stock(): void
    {
        $product = Product::factory()->create(['quantity' => 5, 'active' => true]);

        // Add 4 items
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 4]);

        // Try to add 3 more (total would be 7, exceeds stock of 5)
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 3]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_each_user_has_their_own_cart(): void
    {
        $user2 = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 20, 'active' => true]);

        // user1 adds item
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2]);

        // user2 views their cart — should be empty
        $response = $this->actingAs($user2, 'sanctum')
            ->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonPath('data.items', []);
    }
}
