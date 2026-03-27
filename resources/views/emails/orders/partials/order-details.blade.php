@php
    $formatAddress = static function (array $address): array {
        $lines = [];

        if (! empty($address['name'])) {
            $lines[] = $address['name'];
        }

        $streetLine = trim((string) ($address['street'] ?? ''));

        if ($streetLine !== '') {
            $lines[] = $streetLine;
        }

        $cityStateLine = collect([
            $address['city'] ?? null,
            $address['state'] ?? null,
        ])->filter()->implode(' - ');

        $postalLine = collect([
            $cityStateLine !== '' ? $cityStateLine : null,
            $address['zip_code'] ?? null,
            $address['country'] ?? null,
        ])->filter()->implode(' | ');

        if ($postalLine !== '') {
            $lines[] = $postalLine;
        }

        return $lines;
    };

    $shippingAddressLines = $formatAddress($order->shipping_address ?? []);
    $billingAddressLines = $formatAddress($order->billing_address ?? []);
    $statusLabels = [
        'pending' => 'Pendente',
        'processing' => 'Em processamento',
        'shipped' => 'Enviado',
        'delivered' => 'Entregue',
        'cancelled' => 'Cancelado',
    ];
    $paymentStatusLabels = [
        'pending' => 'Pendente',
        'paid' => 'Pago',
    ];
@endphp

## Detalhes do pedido

**Pedido #{{ $order->id }}**
Data: {{ $order->created_at->format('d/m/Y H:i') }}
Status logistico: {{ $statusLabels[$order->status] ?? ucfirst($order->status) }}
Status do pagamento: {{ $paymentStatusLabels[$order->payment_status] ?? ucfirst($order->payment_status) }}

<x-mail::table>
| Produto | Quantidade | Valor unitario | Total |
|---------|------------|----------------|-------|
@foreach ($order->items as $item)
| {{ $item->product?->name ?? 'Produto removido' }} | {{ $item->quantity }} | R$ {{ number_format($item->unit_price, 2, ',', '.') }} | R$ {{ number_format($item->total_price, 2, ',', '.') }} |
@endforeach
</x-mail::table>

---

**Subtotal:** R$ {{ number_format($order->subtotal, 2, ',', '.') }}
**Impostos:** R$ {{ number_format($order->tax, 2, ',', '.') }}
**Frete:** R$ {{ number_format($order->shipping_cost, 2, ',', '.') }}
**Total:** R$ {{ number_format($order->total, 2, ',', '.') }}

---

**Endereco de entrega**
@forelse ($shippingAddressLines as $line)
{{ $line }}
@empty
Nao informado
@endforelse

**Endereco de cobranca**
@forelse ($billingAddressLines as $line)
{{ $line }}
@empty
Nao informado
@endforelse
