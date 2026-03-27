<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\ProcessOrderPipeline;
use Illuminate\Support\Facades\Log;

class ProcessOrderListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        Log::info('Order created', [
            'order_id' => $event->order->id,
            'user_id' => $event->order->user_id,
            'total' => $event->order->total,
            'status' => $event->order->status,
        ]);

        ProcessOrderPipeline::dispatch($event->order->id);
    }
}
