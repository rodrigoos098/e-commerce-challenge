<?php

namespace App\Listeners;

use App\Events\OrderPaymentConfirmed;
use App\Jobs\SendOrderConfirmationEmail;
use App\Mail\OrderConfirmationMail;

class QueueOrderPaymentConfirmedNotification
{
    /**
     * Handle the event.
     */
    public function handle(OrderPaymentConfirmed $event): void
    {
        SendOrderConfirmationEmail::dispatch($event->order->id, OrderConfirmationMail::TYPE_PAYMENT_CONFIRMED);
    }
}
