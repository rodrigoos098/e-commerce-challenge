<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\SendOrderConfirmationEmail;
use App\Mail\OrderConfirmationMail;

class QueueOrderCreatedNotification
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        SendOrderConfirmationEmail::dispatch($event->order->id, OrderConfirmationMail::TYPE_CREATED);
    }
}
