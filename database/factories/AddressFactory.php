<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['Casa', 'Trabalho', 'Apartamento']),
            'recipient_name' => fake()->name(),
            'street' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip_code' => fake()->postcode(),
            'country' => 'Brasil',
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ];
    }

    /**
     * Indicate the address is the default shipping address.
     */
    public function defaultShipping(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default_shipping' => true,
        ]);
    }

    /**
     * Indicate the address is the default billing address.
     */
    public function defaultBilling(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default_billing' => true,
        ]);
    }
}
