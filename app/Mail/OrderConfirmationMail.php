<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public const TYPE_CREATED = 'created';

    public const TYPE_PAYMENT_CONFIRMED = 'payment_confirmed';

    public const TYPE_CANCELLED = 'cancelled';

    public const TYPE_SHIPPED = 'shipped';

    public const TYPE_DELIVERED = 'delivered';

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly Order $order,
        public readonly string $notificationType = self::TYPE_CREATED,
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->resolveSubject(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: $this->resolveMarkdownView(),
            with: [
                'order' => $this->order->loadMissing(['user', 'items.product']),
                'notificationType' => $this->notificationType,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function resolveSubject(): string
    {
        return match ($this->notificationType) {
            self::TYPE_PAYMENT_CONFIRMED => 'Pagamento confirmado do pedido #'.$this->order->id,
            self::TYPE_CANCELLED => 'Pedido #'.$this->order->id.' cancelado',
            self::TYPE_SHIPPED => 'Pedido #'.$this->order->id.' enviado',
            self::TYPE_DELIVERED => 'Pedido #'.$this->order->id.' entregue',
            default => 'Confirmacao do pedido #'.$this->order->id,
        };
    }

    private function resolveMarkdownView(): string
    {
        return match ($this->notificationType) {
            self::TYPE_PAYMENT_CONFIRMED => 'emails.orders.payment-confirmed',
            self::TYPE_CANCELLED => 'emails.orders.cancelled',
            self::TYPE_SHIPPED => 'emails.orders.shipped',
            self::TYPE_DELIVERED => 'emails.orders.delivered',
            default => 'emails.orders.confirmation',
        };
    }
}
