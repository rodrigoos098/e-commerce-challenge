<?php

namespace Tests\Unit\Services;

use App\Services\CartPricingService;
use PHPUnit\Framework\TestCase;

class CartPricingServiceTest extends TestCase
{
    public function test_returns_unavailable_shipping_for_empty_cart(): void
    {
        $quote = (new CartPricingService())->calculateShipping(0.0, '01310-100');

        $this->assertSame(0.0, $quote['cost']);
        $this->assertSame('01310100', $quote['zip_code']);
        $this->assertSame('Frete indisponivel', $quote['rule_label']);
        $this->assertTrue($quote['is_free']);
    }

    public function test_returns_free_shipping_when_subtotal_reaches_threshold(): void
    {
        $quote = (new CartPricingService())->calculateShipping(200.0, '98765-000');

        $this->assertSame(0.0, $quote['cost']);
        $this->assertSame('98765000', $quote['zip_code']);
        $this->assertSame('Frete gratis', $quote['rule_label']);
        $this->assertTrue($quote['is_free']);
    }

    public function test_returns_default_estimate_when_zip_code_is_missing(): void
    {
        $quote = (new CartPricingService())->calculateShipping(150.0);

        $this->assertSame(19.9, $quote['cost']);
        $this->assertNull($quote['zip_code']);
        $this->assertSame('Estimativa padrao', $quote['rule_label']);
        $this->assertFalse($quote['is_free']);
    }

    public function test_calculates_shipping_by_zip_code_range(): void
    {
        $service = new CartPricingService();

        $quotesByZipCode = [
            '01310-100' => ['cost' => 14.9, 'label' => 'Frete para o seu endereco'],
            '35700-000' => ['cost' => 21.9, 'label' => 'Frete para o seu endereco'],
            '98765-000' => ['cost' => 27.9, 'label' => 'Frete para o seu endereco'],
        ];

        foreach ($quotesByZipCode as $zipCode => $expectedQuote) {
            $quote = $service->calculateShipping(150.0, $zipCode);

            $this->assertSame($expectedQuote['cost'], $quote['cost']);
            $this->assertSame($expectedQuote['label'], $quote['rule_label']);
            $this->assertFalse($quote['is_free']);
        }
    }

    public function test_normalize_zip_code_keeps_only_digits_and_limits_to_eight_characters(): void
    {
        $service = new CartPricingService();

        $this->assertSame('01310100', $service->normalizeZipCode('01310-100'));
        $this->assertSame('12345678', $service->normalizeZipCode('12.345-6789'));
        $this->assertNull($service->normalizeZipCode('CEP invalido'));
        $this->assertNull($service->normalizeZipCode(null));
    }
}
