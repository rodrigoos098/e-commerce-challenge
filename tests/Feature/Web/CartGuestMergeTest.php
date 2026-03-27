<?php

namespace Tests\Feature\Web;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CartGuestMergeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    }

    public function test_guest_can_add_item_to_session_cart_and_merge_it_after_login(): void
    {
        $product = Product::factory()->create([
            'quantity' => 10,
            'active' => true,
        ]);

        $this->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $guestSessionId = session()->getId();

        $user = User::factory()->create([
            'email' => 'cliente@example.com',
            'password' => bcrypt('Password123!'),
        ]);
        $user->assignRole('customer');

        $this->post('/login', [
            'email' => 'cliente@example.com',
            'password' => 'Password123!',
        ])->assertRedirect('/');

        $userCart = Cart::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $userCart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseMissing('carts', [
            'session_id' => $guestSessionId,
        ]);
    }
}
