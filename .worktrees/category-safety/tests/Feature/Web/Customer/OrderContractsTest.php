<?php

namespace Tests\Feature\Web\Customer;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderContractsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    }

    public function test_customer_order_show_exposes_structured_addresses(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $order = Order::factory()->for($customer)->create([
            'shipping_address' => [
                'name' => 'Cliente Teste',
                'street' => 'Rua Alfa, 10',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'zip_code' => '01000-000',
                'country' => 'BR',
            ],
            'billing_address' => [
                'name' => 'Cliente Teste',
                'street' => 'Rua Beta, 20',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'zip_code' => '02000-000',
                'country' => 'BR',
            ],
        ]);

        $this->actingAs($customer)
            ->get("/customer/orders/{$order->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->where('order.id', $order->id)
                ->where('order.shipping_address.street', 'Rua Alfa, 10')
                ->where('order.shipping_address.zip_code', '01000-000')
                ->where('order.billing_address.street', 'Rua Beta, 20')
                ->where('order.billing_address.zip_code', '02000-000'));
    }
}
