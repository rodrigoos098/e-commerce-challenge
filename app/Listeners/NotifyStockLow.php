<?php

namespace App\Listeners;

use App\Events\StockLow;
use Illuminate\Support\Facades\Log;

class NotifyStockLow
{
    /**
     * Handle the event.
     */
    public function handle(StockLow $event): void
    {
        Log::warning('Product low stock alert', [
            'product_id' => $event->product->id,
            'product_name' => $event->product->name,
            'current_quantity' => $event->product->quantity,
            'min_quantity' => $event->product->min_quantity,
        ]);
    }
}
