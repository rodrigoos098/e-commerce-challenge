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
    $orderTotal = $order->items->sum('total_price');
@endphp

<div style="background-color: #F5EFE0; border-radius: 12px; padding: 20px 24px; margin: 24px 0 8px 0;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td style="padding-bottom: 4px;">
<span style="font-family: 'Playfair Display', Georgia, serif; font-size: 20px; font-weight: 700; color: #2D261D;">Pedido #{{ $order->id }}</span>
</td>
</tr>
<tr>
<td style="color: #8C7B66; font-size: 13px; line-height: 1.8; font-family: 'DM Sans', sans-serif;">
{{ $order->created_at->format('d/m/Y') }} às {{ $order->created_at->format('H:i') }} · Status: {{ $statusLabels[$order->status] ?? ucfirst($order->status) }} · Pagamento: {{ $paymentStatusLabels[$order->payment_status] ?? ucfirst($order->payment_status) }}
</td>
</tr>
</table>
</div>

<x-mail::table>
| Produto | Qtd. | Unitário | Total |
|---------|------|----------|-------|
@foreach ($order->items as $item)
| {{ $item->product?->name ?? 'Produto removido' }} | {{ $item->quantity }} | R$ {{ number_format($item->unit_price, 2, ',', '.') }} | R$ {{ number_format($item->total_price, 2, ',', '.') }} |
@endforeach
</x-mail::table>

<div style="text-align: right; padding: 0 8px 16px 0;">
<span style="color: #8C7B66; font-size: 13px; font-family: 'DM Sans', sans-serif;">Total do pedido:</span>
<span style="font-family: 'Playfair Display', Georgia, serif; font-size: 22px; font-weight: 800; color: #A67C1F; margin-left: 8px;">R$ {{ number_format($orderTotal, 2, ',', '.') }}</span>
</div>

@if (count($shippingAddressLines) > 0)
<div style="border-top: 1px solid #F5EFE0; padding-top: 16px; margin-top: 8px;">
<p style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; color: #8C7B66; margin-bottom: 6px; font-family: 'DM Sans', sans-serif;">Endereco de entrega</p>
@foreach ($shippingAddressLines as $line)
<p style="font-size: 14px; color: #2D261D; margin: 0; line-height: 1.6; font-family: 'DM Sans', sans-serif;">{{ $line }}</p>
@endforeach
</div>
@endif

@if (count($billingAddressLines) > 0)
<div style="border-top: 1px solid #F5EFE0; padding-top: 16px; margin-top: 8px;">
<p style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; color: #8C7B66; margin-bottom: 6px; font-family: 'DM Sans', sans-serif;">Endereco de cobranca</p>
@foreach ($billingAddressLines as $line)
<p style="font-size: 14px; color: #2D261D; margin: 0; line-height: 1.6; font-family: 'DM Sans', sans-serif;">{{ $line }}</p>
@endforeach
</div>
@endif

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
