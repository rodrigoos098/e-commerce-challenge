<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = Category::pluck('id');
        $tagIds = Tag::pluck('id');

        Product::factory()
            ->count(50)
            ->create(['category_id' => fn () => $categoryIds->random()])
            ->each(function (Product $product) use ($tagIds): void {
                $product->tags()->sync($tagIds->random(rand(1, 4))->toArray());
            });

        // A few low-stock products for demonstration
        Product::factory()
            ->count(5)
            ->lowStock()
            ->create(['category_id' => fn () => $categoryIds->random()]);
    }
}
