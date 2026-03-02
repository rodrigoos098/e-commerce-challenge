<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\StockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessOrderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(StockService $stockService): void
    {
        foreach ($this->order->items as $item) {
            $stockService->decreaseStock(
                productId: $item->product_id,
                quantity: $item->quantity,
                orderId: $this->order->id,
            );
        }
    }
}
