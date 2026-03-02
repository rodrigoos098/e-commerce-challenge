<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\StockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateStockAfterOrder implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
    ) {}

    /**
     * Execute the job.
     * Decreases stock for each item in the order, recording a 'venda' movement.
     * This job is an alternative to ProcessOrderJob and can be dispatched
     * independently when stock adjustment needs to run in isolation.
     */
    public function handle(StockService $stockService): void
    {
        Log::info('UpdateStockAfterOrder: processing stock for order', [
            'order_id' => $this->order->id,
            'items_count' => $this->order->items->count(),
        ]);

        foreach ($this->order->items as $item) {
            $stockService->decreaseStock(
                productId: $item->product_id,
                quantity: $item->quantity,
                orderId: $this->order->id,
            );
        }

        Log::info('UpdateStockAfterOrder: stock updated successfully', [
            'order_id' => $this->order->id,
        ]);
    }
}
