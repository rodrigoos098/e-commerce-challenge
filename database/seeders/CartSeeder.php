<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::query()
            ->where('active', true)
            ->where('quantity', '>', 0)
            ->get();

        User::role('customer')
            ->get()
            ->take(4)
            ->each(function (User $user) use ($products): void {
                if ($products->isEmpty()) {
                    return;
                }

                $cart = Cart::query()->firstOrCreate(['user_id' => $user->id], ['user_id' => $user->id]);
                $selectedProducts = $products->shuffle()->take(min(rand(1, 3), $products->count()));

                foreach ($selectedProducts as $product) {
                    CartItem::query()->updateOrCreate(
                        [
                            'cart_id' => $cart->id,
                            'product_id' => $product->id,
                        ],
                        [
                            'quantity' => rand(1, min(3, max(1, $product->quantity))),
                        ]
                    );
                }
            });
    }
}
