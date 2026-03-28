<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderConfirmationMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_mail_renders_with_api_address_payload(): void
    {
        $order = $this->createOrderWithItem([
            'street' => 'Rua das Flores, 100',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'zip_code' => '01000-000',
            'country' => 'BR',
        ]);

        $html = (new OrderConfirmationMail($order))->render();

        $this->assertStringContainsString('Recebemos seu pedido', $html);
        $this->assertStringContainsString('Rua das Flores, 100', $html);
        $this->assertStringContainsString('Sao Paulo - SP | 01000-000 | BR', $html);
        $this->assertStringNotContainsString('Undefined array key', $html);
    }

    public function test_confirmation_mail_renders_with_web_address_snapshot(): void
    {
        $address = [
            'name' => 'Maria da Silva',
            'street' => 'Av. Brasil, 2000',
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
            'zip_code' => '20000-000',
            'country' => 'BR',
        ];

        $order = $this->createOrderWithItem($address, $address);

        $html = (new OrderConfirmationMail($order))->render();

        $this->assertStringContainsString('Maria da Silva', $html);
        $this->assertStringContainsString('Endereço de entrega', $html);
        $this->assertStringContainsString('Endereço de cobrança', $html);
        $this->assertStringContainsString('Av. Brasil, 2000', $html);
    }

    /**
     * @param  array<string, string>  $shippingAddress
     * @param  array<string, string>|null  $billingAddress
     */
    private function createOrderWithItem(array $shippingAddress, ?array $billingAddress = null): Order
    {
        $user = User::factory()->create(['name' => 'Cliente Teste']);
        $product = Product::factory()->create(['name' => 'Notebook Gamer']);

        $order = Order::factory()
            ->for($user)
            ->create([
                'shipping_address' => $shippingAddress,
                'billing_address' => $billingAddress ?? $shippingAddress,
                'status' => 'pending',
                'subtotal' => 1000,
                'tax' => 100,
                'shipping_cost' => 20,
                'total' => 1120,
            ]);

        OrderItem::factory()
            ->for($order)
            ->for($product)
            ->create([
                'quantity' => 1,
                'unit_price' => 1000,
                'total_price' => 1000,
            ]);

        return $order->fresh();
    }
}
