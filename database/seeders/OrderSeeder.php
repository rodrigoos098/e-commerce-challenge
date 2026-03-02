<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerIds = User::role('customer')->pluck('id');
        $productIds = Product::pluck('id');

        Order::factory()
            ->count(20)
            ->create(['user_id' => fn () => $customerIds->random()])
            ->each(function (Order $order) use ($productIds): void {
                $itemCount = rand(1, 4);
                $selectedProductIds = $productIds->random($itemCount);

                foreach ($selectedProductIds as $productId) {
                    $product = Product::find($productId);
                    $quantity = rand(1, 3);
                    $unitPrice = $product->price;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => round($unitPrice * $quantity, 2),
                    ]);
                }
            });
    }
}
