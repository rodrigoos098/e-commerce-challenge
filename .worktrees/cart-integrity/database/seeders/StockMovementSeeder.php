<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class StockMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::query()->inRandomOrder()->take(12)->get();

        foreach ($products as $product) {
            $type = fake()->randomElement(['entrada', 'saida', 'ajuste', 'devolucao']);
            $quantity = 0;
            $newQuantity = $product->quantity;

            if ($type === 'entrada') {
                $quantity = rand(5, 25);
                $newQuantity = $product->quantity + $quantity;
            }

            if ($type === 'devolucao') {
                $quantity = rand(1, 5);
                $newQuantity = $product->quantity + $quantity;
            }

            if ($type === 'saida') {
                $quantity = min($product->quantity, rand(1, 5));

                if ($quantity === 0) {
                    $type = 'entrada';
                    $quantity = rand(5, 10);
                    $newQuantity = $product->quantity + $quantity;
                } else {
                    $newQuantity = max(0, $product->quantity - $quantity);
                }
            }

            if ($type === 'ajuste') {
                $quantity = rand(5, 40);
                $newQuantity = $quantity;
            }

            $product->update(['quantity' => $newQuantity]);

            StockMovement::query()->create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => match ($type) {
                    'entrada' => 'Seeded restock',
                    'saida' => 'Seeded manual stock output',
                    'ajuste' => 'Seeded stock adjustment',
                    'devolucao' => 'Seeded return',
                    default => 'Seeded stock movement',
                },
                'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
                'updated_at' => now(),
            ]);
        }
    }
}
