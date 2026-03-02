<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rootCategories = Category::factory()->count(5)->create();

        $rootCategories->each(function (Category $parent): void {
            Category::factory()->count(3)->create(['parent_id' => $parent->id]);
        });
    }
}
