<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 20, 800);
        $tax = round($subtotal * 0.1, 2);
        $shippingCost = fake()->randomFloat(2, 5, 30);
        $total = $subtotal + $tax + $shippingCost;

        $address = [
            'street' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip_code' => fake()->postcode(),
            'country' => 'BR',
        ];

        return [
            'user_id' => User::factory(),
            'status' => fake()->randomElement(Order::STATUSES),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_cost' => $shippingCost,
            'total' => $total,
            'shipping_address' => $address,
            'billing_address' => $address,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    /**
     * Indicate the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'delivered']);
    }
}
