<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(fake()->numberBetween(2, 4), true);
        $price = fake()->randomFloat(2, 20, 500);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'price' => $price,
            'cost_price' => round($price * fake()->randomFloat(2, 0.3, 0.6), 2),
            'quantity' => fake()->numberBetween(0, 100),
            'min_quantity' => fake()->numberBetween(1, 10),
            'active' => true,
            'category_id' => Category::factory(),
        ];
    }

    /**
     * Indicate the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['active' => false]);
    }

    /**
     * Indicate the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => ['quantity' => 0]);
    }

    /**
     * Indicate the product has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 2,
            'min_quantity' => 10,
        ]);
    }
}
