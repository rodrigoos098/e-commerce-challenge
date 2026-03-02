<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => fake()->randomElement(StockMovement::TYPES),
            'quantity' => fake()->numberBetween(1, 50),
            'reason' => fake()->optional(0.7)->sentence(),
            'reference_type' => null,
            'reference_id' => null,
        ];
    }
}
