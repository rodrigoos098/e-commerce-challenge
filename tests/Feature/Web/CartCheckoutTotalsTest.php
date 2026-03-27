<?php

namespace Tests\Feature\Web;

use App\Models\Address;
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
        Address::factory()->for($user)->defaultShipping()->create([
            'zip_code' => '01310-100',
        ]);
        $product = Product::factory()->create([
            'price' => 75.0,
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
                ->where('cart.subtotal', 150)
                ->where('cart.tax', 15)
                ->where('cart.shipping_cost', 14.9)
                ->where('cart.shipping_rule_label', 'Faixa de CEP 0-2')
                ->where('cart.total', 179.9));

        $this->actingAs($user)
            ->get('/customer/checkout')
            ->assertRedirect(route('verification.notice'));

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user)
            ->get('/customer/checkout')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Checkout')
                ->where('cart.subtotal', 150)
                ->where('cart.tax', 15)
                ->where('cart.shipping_cost', 14.9)
                ->where('cart.shipping_rule_label', 'Faixa de CEP 0-2')
                ->where('cart.total', 179.9));
    }

    public function test_checkout_recalculates_shipping_using_the_selected_shipping_address(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('customer');

        Address::factory()->for($user)->defaultShipping()->create([
            'zip_code' => '01310-100',
        ]);
        /** @var Address $selectedAddress */
        $selectedAddress = Address::factory()->for($user)->create([
            'zip_code' => '98765-000',
            'is_default_shipping' => false,
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

        $this->actingAs($user)
            ->get('/customer/checkout?shipping_mode=saved&shipping_address_id='.$selectedAddress->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Checkout')
                ->where('cart.shipping_zip_code', '98765000')
                ->where('cart.shipping_cost', 27.9)
                ->where('cart.shipping_rule_label', 'Faixa de CEP 7-9')
                ->where('cart.total', 115.9));
    }
}
