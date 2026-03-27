<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
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
        $createdOrders = 0;

        while ($createdOrders < 20) {
            $availableProducts = Product::query()
                ->where('active', true)
                ->where('quantity', '>', 0)
                ->get();

            if ($availableProducts->isEmpty()) {
                return;
            }

            $status = fake()->randomElement(Order::STATUSES);
            $order = Order::factory()->create([
                'user_id' => $customerIds->random(),
                'status' => $status,
                'subtotal' => 0,
                'tax' => 0,
                'shipping_cost' => 0,
                'total' => 0,
                'created_at' => fake()->dateTimeBetween('-6 days', 'now'),
                'updated_at' => now(),
            ]);

            $selectedProducts = $availableProducts
                ->shuffle()
                ->take(min(rand(1, 4), $availableProducts->count()));

            $subtotal = 0;

            foreach ($selectedProducts as $product) {
                $maxQuantity = min(3, max(1, $product->quantity));
                $quantity = rand(1, $maxQuantity);
                $unitPrice = (float) $product->price;
                $lineTotal = round($unitPrice * $quantity, 2);

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->created_at,
                ]);

                $subtotal += $lineTotal;

                if ($status !== 'cancelled') {
                    $product->decrement('quantity', $quantity);

                    StockMovement::query()->create([
                        'product_id' => $product->id,
                        'type' => 'venda',
                        'quantity' => $quantity,
                        'reason' => 'Seeded order sale',
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'created_at' => $order->created_at,
                        'updated_at' => $order->created_at,
                    ]);
                }
            }

            $tax = round($subtotal * 0.1, 2);
            $shippingCost = $subtotal >= 250 ? 0.0 : 19.90;
            $total = round($subtotal + $tax + $shippingCost, 2);

            $order->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'updated_at' => $order->created_at,
            ]);

            $createdOrders++;
        }
    }
}
