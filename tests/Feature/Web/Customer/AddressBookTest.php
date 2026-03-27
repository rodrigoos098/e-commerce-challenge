<?php

namespace Tests\Feature\Web\Customer;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AddressBookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    }

    private function createCustomer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function addressPayload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Casa',
            'recipient_name' => 'Cliente Teste',
            'street' => 'Rua das Flores, 123',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip_code' => '01310-100',
            'country' => 'Brasil',
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ], $overrides);
    }

    public function test_customer_can_view_their_address_book_page(): void
    {
        $customer = $this->createCustomer();
        Address::factory()->count(2)->for($customer)->create();
        Address::factory()->create();

        $this->actingAs($customer)
            ->get('/customer/addresses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Addresses/Index')
                ->has('addresses', 2));
    }

    public function test_customer_can_create_update_and_delete_addresses_with_default_fallbacks(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($customer)
            ->post('/customer/addresses', $this->addressPayload())
            ->assertRedirect();

        $firstAddress = $customer->addresses()->firstOrFail();

        $this->assertTrue($firstAddress->is_default_shipping);
        $this->assertTrue($firstAddress->is_default_billing);

        $this->actingAs($customer)
            ->post('/customer/addresses', $this->addressPayload([
                'label' => 'Trabalho',
                'street' => 'Avenida Central, 500',
                'is_default_shipping' => true,
            ]))
            ->assertRedirect();

        $secondAddress = $customer->addresses()->where('label', 'Trabalho')->firstOrFail();

        $this->assertTrue($secondAddress->fresh()->is_default_shipping);
        $this->assertFalse($firstAddress->fresh()->is_default_shipping);
        $this->assertTrue($firstAddress->fresh()->is_default_billing);

        $this->actingAs($customer)
            ->put("/customer/addresses/{$secondAddress->id}", $this->addressPayload([
                'label' => 'Trabalho',
                'street' => 'Avenida Central, 999',
                'is_default_shipping' => true,
                'is_default_billing' => true,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('addresses', [
            'id' => $secondAddress->id,
            'street' => 'Avenida Central, 999',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);

        $this->assertFalse($firstAddress->fresh()->is_default_billing);

        $this->actingAs($customer)
            ->delete("/customer/addresses/{$secondAddress->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('addresses', [
            'id' => $secondAddress->id,
        ]);

        $this->assertTrue($firstAddress->fresh()->is_default_shipping);
        $this->assertTrue($firstAddress->fresh()->is_default_billing);
    }

    public function test_customer_cannot_manage_another_users_addresses(): void
    {
        $customer = $this->createCustomer();
        $otherCustomer = $this->createCustomer();
        $address = Address::factory()->for($otherCustomer)->create();

        $this->actingAs($customer)
            ->put("/customer/addresses/{$address->id}", $this->addressPayload(['label' => 'Invadido']))
            ->assertForbidden();

        $this->actingAs($customer)
            ->put("/customer/addresses/{$address->id}/default/shipping")
            ->assertForbidden();

        $this->actingAs($customer)
            ->delete("/customer/addresses/{$address->id}")
            ->assertForbidden();
    }

    public function test_checkout_can_use_saved_addresses_and_order_keeps_snapshot_after_address_update(): void
    {
        $customer = $this->createCustomer();
        $shippingAddress = Address::factory()->for($customer)->defaultShipping()->create([
            'label' => 'Casa',
            'recipient_name' => 'Maria Cliente',
            'street' => 'Rua Um, 10',
        ]);
        $billingAddress = Address::factory()->for($customer)->defaultBilling()->create([
            'label' => 'Escritorio',
            'recipient_name' => 'Financeiro Cliente',
            'street' => 'Rua Dois, 20',
        ]);
        $product = Product::factory()->create([
            'price' => 120.0,
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

        $this->actingAs($customer)
            ->get('/customer/checkout')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Checkout')
                ->has('addresses', 2)
                ->where('addresses.0.id', $shippingAddress->id));

        $this->actingAs($customer)
            ->post('/customer/orders', [
                'shipping_mode' => 'saved',
                'shipping_address_id' => $shippingAddress->id,
                'same_billing' => false,
                'billing_mode' => 'saved',
                'billing_address_id' => $billingAddress->id,
                'payment_simulated' => true,
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $customer->id)->latest('id')->firstOrFail();

        $this->assertSame('Maria Cliente', $order->shipping_address['name']);
        $this->assertSame('Rua Um, 10', $order->shipping_address['street']);
        $this->assertSame('Financeiro Cliente', $order->billing_address['name']);
        $this->assertSame('Rua Dois, 20', $order->billing_address['street']);

        $shippingAddress->update([
            'recipient_name' => 'Nome Alterado',
            'street' => 'Rua Nova, 999',
        ]);

        $order->refresh();

        $this->assertSame('Maria Cliente', $order->shipping_address['name']);
        $this->assertSame('Rua Um, 10', $order->shipping_address['street']);
    }

    public function test_checkout_can_create_order_with_manual_billing_when_same_billing_is_false(): void
    {
        $customer = $this->createCustomer();
        $product = Product::factory()->create([
            'price' => 80.0,
            'quantity' => 10,
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
            ->post('/customer/orders', [
                'shipping_mode' => 'new',
                'shipping_name' => 'Maria Cliente',
                'shipping_street' => 'Rua Um, 10',
                'shipping_city' => 'Sao Paulo',
                'shipping_state' => 'SP',
                'shipping_zip' => '01000-000',
                'shipping_country' => 'Brasil',
                'same_billing' => false,
                'billing_mode' => 'new',
                'billing_name' => 'Financeiro Cliente',
                'billing_street' => 'Rua Dois, 20',
                'billing_city' => 'Campinas',
                'billing_state' => 'SP',
                'billing_zip' => '13000-000',
                'billing_country' => 'Brasil',
                'notes' => 'Entregar em horario comercial.',
                'payment_simulated' => true,
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $customer->id)->latest('id')->firstOrFail();

        $this->assertSame('Maria Cliente', $order->shipping_address['name']);
        $this->assertSame('Financeiro Cliente', $order->billing_address['name']);
        $this->assertSame('Rua Dois, 20', $order->billing_address['street']);
        $this->assertSame('Entregar em horario comercial.', $order->notes);
    }
}
