<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Jobs\SendOrderConfirmationEmail;
use App\Mail\OrderConfirmationMail;

class QueueOrderCancelledNotification
{
    /**
     * Handle the event.
     */
    public function handle(OrderCancelled $event): void
    {
        SendOrderConfirmationEmail::dispatch($event->order->id, OrderConfirmationMail::TYPE_CANCELLED);
    }
}
