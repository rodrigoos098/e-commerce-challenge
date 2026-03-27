<?php

namespace App\Listeners;

use App\Events\ProductCreated;
use Illuminate\Support\Facades\Log;

class LogProductCreated
{
    /**
     * Handle the event.
     */
    public function handle(ProductCreated $event): void
    {
        Log::info('Product created', [
            'product_id' => $event->product->id,
            'product_name' => $event->product->name,
            'category_id' => $event->product->category_id,
            'price' => $event->product->price,
        ]);
    }
}
