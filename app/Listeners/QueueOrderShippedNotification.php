<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Jobs\SendOrderConfirmationEmail;
use App\Mail\OrderConfirmationMail;

class QueueOrderShippedNotification
{
    /**
     * Handle the event.
     */
    public function handle(OrderShipped $event): void
    {
        SendOrderConfirmationEmail::dispatch($event->order->id, OrderConfirmationMail::TYPE_SHIPPED);
    }
}
