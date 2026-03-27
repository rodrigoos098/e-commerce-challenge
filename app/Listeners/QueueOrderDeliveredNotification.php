<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Jobs\SendOrderConfirmationEmail;
use App\Mail\OrderConfirmationMail;

class QueueOrderDeliveredNotification
{
    /**
     * Handle the event.
     */
    public function handle(OrderDelivered $event): void
    {
        SendOrderConfirmationEmail::dispatch($event->order->id, OrderConfirmationMail::TYPE_DELIVERED);
    }
}
