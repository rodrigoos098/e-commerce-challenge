<?php

namespace App\DTOs;

use Illuminate\Foundation\Http\FormRequest;

readonly class OrderDTO
{
    /**
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<string, mixed>  $billingAddress
     */
    public function __construct(
        public int $userId,
        public array $shippingAddress,
        public array $billingAddress,
        public ?string $notes = null,
        public bool $paymentSimulated = false,
    ) {
    }

    /**
     * Create an OrderDTO from a Form Request.
     */
    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            userId: $request->user()->id,
            shippingAddress: self::normalizeAddress($request->input('shipping_address', [])),
            billingAddress: self::normalizeAddress($request->input('billing_address', [])),
            notes: $request->input('notes'),
            paymentSimulated: $request->boolean('payment_simulated'),
        );
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>
     */
    private static function normalizeAddress(array $address): array
    {
        if (isset($address['zip']) && ! isset($address['zip_code'])) {
            $address['zip_code'] = $address['zip'];
            unset($address['zip']);
        }

        return $address;
    }

    /**
     * Convert DTO to array for Eloquent operations.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'shipping_address' => $this->shippingAddress,
            'billing_address' => $this->billingAddress,
            'notes' => $this->notes,
        ];
    }
}
