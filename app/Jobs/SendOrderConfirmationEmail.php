<?php

namespace App\Jobs;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 86400;

    public function __construct(
        public readonly int $orderId,
        public readonly string $notificationType,
    ) {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return sprintf('order-notification:%s:%d', $this->notificationType, $this->orderId);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::query()
            ->with(['user', 'items.product'])
            ->find($this->orderId);

        if (! $order instanceof Order || ! $order->user?->email) {
            return;
        }

        Mail::to($order->user->email)
            ->send(new OrderConfirmationMail($order, $this->notificationType));
    }
}
