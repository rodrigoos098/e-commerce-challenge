<?php

namespace Tests\Feature\Web\Customer;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
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
        /** @var User $customer */
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

    public function test_customer_order_show_exposes_cancel_capability(): void
    {
        /** @var User $customer */
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $order = Order::factory()->for($customer)->create([
            'status' => 'pending',
        ]);

        $this->actingAs($customer)
            ->get("/customer/orders/{$order->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->where('order.id', $order->id)
                ->where('order.can_cancel', true));
    }

    public function test_customer_order_show_includes_items_with_product_data(): void
    {
        /** @var User $customer */
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $order = Order::factory()->for($customer)->create();
        $product = Product::factory()->create([
            'name' => 'Cafeteira Programavel',
            'slug' => 'cafeteira-programavel',
        ]);

        OrderItem::factory()->for($order)->for($product)->create([
            'quantity' => 2,
            'unit_price' => 149.9,
            'total_price' => 299.8,
        ]);

        $this->actingAs($customer)
            ->get("/customer/orders/{$order->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->where('order.id', $order->id)
                ->has('order.items', 1)
                ->where('order.items.0.quantity', 2)
                ->where('order.items.0.product.name', 'Cafeteira Programavel')
                ->where('order.items.0.product.slug', 'cafeteira-programavel'));
    }

    public function test_customer_order_show_exposes_mock_payment_fields_without_overwriting_logistics_status(): void
    {
        /** @var User $customer */
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $order = Order::factory()->for($customer)->create([
            'status' => 'pending',
            'payment_status' => 'paid',
            'payment_method' => Order::MOCK_PAYMENT_METHOD,
            'paid_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get("/customer/orders/{$order->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->where('order.id', $order->id)
                ->where('order.status', 'pending')
                ->where('order.payment_status', 'paid')
                ->where('order.payment_method', Order::MOCK_PAYMENT_METHOD)
                ->where('order.paid_at', $order->paid_at?->toIso8601String()));
    }
}
