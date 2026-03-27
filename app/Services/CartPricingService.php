<?php

namespace App\Services;

class CartPricingService
{
    private const FREE_SHIPPING_THRESHOLD = 200.0;

    private const DEFAULT_SHIPPING_COST = 19.9;

    /**
     * Calculate a shipping quote based on CEP range.
     *
     * @return array{cost: float, zip_code: ?string, rule_label: string, rule_description: string, is_free: bool}
     */
    public function calculateShipping(float $subtotal, ?string $zipCode = null): array
    {
        $normalizedZipCode = $this->normalizeZipCode($zipCode);

        if ($subtotal <= 0) {
            return [
                'cost' => 0.0,
                'zip_code' => $normalizedZipCode,
                'rule_label' => 'Frete indisponivel',
                'rule_description' => 'Adicione produtos ao carrinho para calcular o frete conforme o CEP de entrega.',
                'is_free' => true,
            ];
        }

        if ($subtotal >= self::FREE_SHIPPING_THRESHOLD) {
            return [
                'cost' => 0.0,
                'zip_code' => $normalizedZipCode,
                'rule_label' => 'Frete gratis',
                'rule_description' => 'Frete gratis para pedidos a partir de R$ 200,00, independente da faixa de CEP.',
                'is_free' => true,
            ];
        }

        if ($normalizedZipCode === null) {
            return [
                'cost' => self::DEFAULT_SHIPPING_COST,
                'zip_code' => null,
                'rule_label' => 'Estimativa padrao',
                'rule_description' => 'Informe um CEP para calcular o valor exato. Sem CEP informado, usamos a estimativa padrao de R$ 19,90.',
                'is_free' => false,
            ];
        }

        $firstDigit = (int) $normalizedZipCode[0];

        if ($firstDigit <= 2) {
            return [
                'cost' => 14.9,
                'zip_code' => $normalizedZipCode,
                'rule_label' => 'Frete para o seu endereco',
                'rule_description' => "Frete calculado para o CEP {$normalizedZipCode}: R$ 14,90.",
                'is_free' => false,
            ];
        }

        if ($firstDigit <= 6) {
            return [
                'cost' => 21.9,
                'zip_code' => $normalizedZipCode,
                'rule_label' => 'Frete para o seu endereco',
                'rule_description' => "Frete calculado para o CEP {$normalizedZipCode}: R$ 21,90.",
                'is_free' => false,
            ];
        }

        return [
            'cost' => 27.9,
            'zip_code' => $normalizedZipCode,
            'rule_label' => 'Frete para o seu endereco',
            'rule_description' => "Frete calculado para o CEP {$normalizedZipCode}: R$ 27,90.",
            'is_free' => false,
        ];
    }

    public function normalizeZipCode(?string $zipCode): ?string
    {
        if ($zipCode === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $zipCode);

        if (! is_string($digits) || $digits === '') {
            return null;
        }

        return substr($digits, 0, 8);
    }
}
