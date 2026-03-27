<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessOrderPipeline implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $orderId,
    ) {
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(OrderService $orderService): void
    {
        $order = Order::query()->with(['items.product', 'user'])->find($this->orderId);

        if (! $order instanceof Order) {
            Log::warning('Order processing pipeline skipped because order was not found.', [
                'order_id' => $this->orderId,
            ]);

            return;
        }

        $processedOrder = $orderService->processPendingOrder($order);

        Log::info('Order processing pipeline started.', [
            'order_id' => $processedOrder->id,
            'status' => $processedOrder->status,
            'items_count' => $processedOrder->items->count(),
        ]);

        Log::info('Order processing pipeline completed.', [
            'order_id' => $processedOrder->id,
            'status' => $processedOrder->status,
        ]);
    }
}
