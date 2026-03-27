<?php

namespace Tests\Unit;

use App\Services\CartTotalsService;
use Tests\TestCase;

class CartTotalsServiceTest extends TestCase
{
    public function test_calculates_free_shipping_from_threshold(): void
    {
        $service = new CartTotalsService();
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
    }

    public function test_calculates_fixed_shipping_below_threshold(): void
    {
        $service = new CartTotalsService();
        $items = collect([
            (object) [
                'quantity' => 1,
                'product' => (object) ['price' => 150.0],
            ],
        ]);

        $totals = $service->calculate($items);

        $this->assertSame(150.0, $totals['subtotal']);
        $this->assertSame(15.0, $totals['tax']);
        $this->assertSame(19.9, $totals['shipping_cost']);
        $this->assertSame(184.9, $totals['total']);
    }
}
