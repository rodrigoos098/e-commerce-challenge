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
        $name = fake()->unique()->words(3, true);
        $price = fake()->randomFloat(2, 10, 500);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraphs(2, true),
            'price' => $price,
            'cost_price' => fake()->randomFloat(2, 5, $price * 0.8),
            'quantity' => fake()->numberBetween(0, 200),
            'min_quantity' => fake()->numberBetween(5, 20),
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
