<?php

namespace Tests\Unit;

use App\Services\CartPricingService;
use App\Services\CartTotalsService;
use Tests\TestCase;

class CartTotalsServiceTest extends TestCase
{
    public function test_calculates_free_shipping_from_threshold(): void
    {
        $service = new CartTotalsService(new CartPricingService());
        $items = collect([
            (object) [
                'quantity' => 2,
                'product' => (object) ['price' => 100.0],
            ],
        ]);

        $totals = $service->calculate($items);

        $this->assertSame(200.0, $totals['subtotal']);
        $this->assertSame(20.0, $totals['tax']);
        $this->assertSame(0.0, $totals['shipping_cost']);
        $this->assertSame(220.0, $totals['total']);
        $this->assertTrue($totals['shipping_is_free']);
        $this->assertSame('Frete gratis', $totals['shipping_rule_label']);
    }

    public function test_calculates_shipping_by_zip_code_range_below_free_shipping_threshold(): void
    {
        $service = new CartTotalsService(new CartPricingService());
        $items = collect([
            (object) [
                'quantity' => 1,
                'product' => (object) ['price' => 150.0],
            ],
        ]);

        $totals = $service->calculate($items, '01310-100');

        $this->assertSame(150.0, $totals['subtotal']);
        $this->assertSame(15.0, $totals['tax']);
        $this->assertSame(14.9, $totals['shipping_cost']);
        $this->assertSame(179.9, $totals['total']);
        $this->assertSame('01310100', $totals['shipping_zip_code']);
        $this->assertSame('Faixa de CEP 0-2', $totals['shipping_rule_label']);
    }

    public function test_uses_default_shipping_estimate_when_zip_code_is_missing(): void
    {
        $service = new CartTotalsService(new CartPricingService());
        $items = collect([
            (object) [
                'quantity' => 1,
                'product' => (object) ['price' => 150.0],
            ],
        ]);

        $totals = $service->calculate($items);

        $this->assertSame(19.9, $totals['shipping_cost']);
        $this->assertSame('Estimativa padrao', $totals['shipping_rule_label']);
        $this->assertNull($totals['shipping_zip_code']);
    }

    public function test_resolves_preferred_shipping_zip_code_before_fallback_addresses(): void
    {
        $service = new CartTotalsService(new CartPricingService());

        $zipCode = $service->resolveShippingZipCode('98765-000', [
            ['zip_code' => '01310-100', 'is_default_shipping' => true],
        ]);

        $this->assertSame('98765000', $zipCode);
    }

    public function test_returns_null_when_checkout_uses_new_address_without_zip_code(): void
    {
        $service = new CartTotalsService(new CartPricingService());

        $zipCode = $service->resolveShippingZipCode('', [
            ['zip_code' => '01310-100', 'is_default_shipping' => true],
        ], false);

        $this->assertNull($zipCode);
    }
}
