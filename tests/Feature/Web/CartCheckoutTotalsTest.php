<?php

namespace Tests\Feature\Web;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CartCheckoutTotalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    }

    public function test_cart_and_checkout_share_the_same_totals_rule(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('customer');
        $product = Product::factory()->create([
            'price' => 100.0,
            'quantity' => 10,
            'active' => true,
        ]);
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user)
            ->get('/cart')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Cart')
                ->where('cart.subtotal', 200)
                ->where('cart.tax', 20)
                ->where('cart.shipping_cost', 0)
                ->where('cart.total', 220));

        $this->actingAs($user)
            ->get('/customer/checkout')
            ->assertRedirect(route('verification.notice'));

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user)
            ->get('/customer/checkout')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Checkout')
                ->where('cart.subtotal', 200)
                ->where('cart.tax', 20)
                ->where('cart.shipping_cost', 0)
                ->where('cart.total', 220));
    }
}
