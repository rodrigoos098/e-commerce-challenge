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

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#F5EFE0" style="background-color: #F5EFE0; border-radius: 12px; margin: 24px 0 8px 0;">
<tr>
<td bgcolor="#F5EFE0" style="background-color: #F5EFE0; padding: 20px 24px; border-radius: 12px;">
<p style="font-family: 'Playfair Display', Georgia, 'Times New Roman', serif; font-size: 20px; font-weight: 700; color: #2D261D; margin: 0 0 6px 0; line-height: 1.3;">Pedido #{{ $order->id }}</p>
<p style="color: #5C4E3D; font-size: 13px; line-height: 1.8; font-family: 'DM Sans', -apple-system, 'Segoe UI', Tahoma, sans-serif; margin: 0;">{{ $order->created_at->format('d/m/Y') }} às {{ $order->created_at->format('H:i') }} &nbsp;·&nbsp; Status: {{ $statusLabels[$order->status] ?? ucfirst($order->status) }} &nbsp;·&nbsp; Pagamento: {{ $paymentStatusLabels[$order->payment_status] ?? ucfirst($order->payment_status) }}</p>
</td>
</tr>
</table>

<x-mail::table>
| Produto | Qtd. | Unitário | Total |
|---------|------|----------|-------|
@foreach ($order->items as $item)
| {{ $item->product?->name ?? 'Produto removido' }} | {{ $item->quantity }} | R$ {{ number_format($item->unit_price, 2, ',', '.') }} | R$ {{ number_format($item->total_price, 2, ',', '.') }} |
@endforeach
</x-mail::table>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 4px 0 16px 0;">
<tr>
<td style="padding: 6px 8px; border-bottom: 1px solid #F5EFE0;">
<p style="font-size: 13px; color: #5C4E3D; font-family: 'DM Sans', -apple-system, 'Segoe UI', Tahoma, sans-serif; margin: 0;">Subtotal</p>
</td>
<td align="right" style="padding: 6px 8px; border-bottom: 1px solid #F5EFE0;">
<p style="font-size: 13px; color: #5C4E3D; font-family: 'DM Sans', -apple-system, 'Segoe UI', Tahoma, sans-serif; margin: 0;">R$ {{ number_format($order->subtotal, 2, ',', '.') }}</p>
</td>
</tr>
<tr>
<td style="padding: 6px 8px; border-bottom: 1px solid #F5EFE0;">
<p style="font-size: 13px; color: #5C4E3D; font-family: 'DM Sans', -apple-system, 'Segoe UI', Tahoma, sans-serif; margin: 0;">Frete</p>
</td>
<td align="right" style="padding: 6px 8px; border-bottom: 1px solid #F5EFE0;">
<p style="font-size: 13px; color: #5C4E3D; font-family: 'DM Sans', -apple-system, 'Segoe UI', Tahoma, sans-serif; margin: 0;">R$ {{ number_format($order->shipping_cost, 2, ',', '.') }}</p>
</td>
</tr>
<tr>
<td style="padding: 6px 8px; border-bottom: 1px solid #F5EFE0;">
<p style="font-size: 13px; color: #5C4E3D; font-family: 'DM Sans', -apple-system, 'Segoe UI', Tahoma, sans-serif; margin: 0;">Impostos</p>
</td>
<td align="right" style="padding: 6px 8px; border-bottom: 1px solid #F5EFE0;">
<p style="font-size: 13px; color: #5C4E3D; font-family: 'DM Sans', -apple-system, 'Segoe UI', Tahoma, sans-serif; margin: 0;">R$ {{ number_format($order->tax, 2, ',', '.') }}</p>
</td>
</tr>
<tr>
<td style="padding: 10px 8px;">
<p style="font-family: 'DM Sans', -apple-system, 'Segoe UI', Tahoma, sans-serif; font-size: 14px; font-weight: 700; color: #2D261D; margin: 0;">Total do pedido</p>
</td>
<td align="right" style="padding: 10px 8px;">
<p style="font-family: 'Playfair Display', Georgia, 'Times New Roman', serif; font-size: 22px; font-weight: 800; color: #A67C1F; margin: 0;">R$ {{ number_format($orderTotal, 2, ',', '.') }}</p>
</td>
</tr>
</table>

@if (count($shippingAddressLines) > 0)
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td style="border-top: 1px solid #F5EFE0; padding-top: 16px; margin-top: 8px;">
<p style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; color: #5C4E3D; margin: 0 0 6px 0; font-family: 'DM Sans', -apple-system, 'Segoe UI', Tahoma, sans-serif;">Endereço de entrega</p>
@foreach ($shippingAddressLines as $line)
<p style="font-size: 14px; color: #2D261D; margin: 0; line-height: 1.6; font-family: 'DM Sans', -apple-system, 'Segoe UI', Tahoma, sans-serif;">{{ $line }}</p>
@endforeach
</td>
</tr>
</table>
@endif

@if (count($billingAddressLines) > 0)
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-top: 1px solid #F5EFE0; padding-top: 16px; margin-top: 8px;">
<tr>
<td style="border-top: 1px solid #F5EFE0; padding-top: 16px;">
<p style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; color: #5C4E3D; margin: 0 0 6px 0; font-family: 'DM Sans', -apple-system, 'Segoe UI', Tahoma, sans-serif;">Endereço de cobrança</p>
@foreach ($billingAddressLines as $line)
<p style="font-size: 14px; color: #2D261D; margin: 0; line-height: 1.6; font-family: 'DM Sans', -apple-system, 'Segoe UI', Tahoma, sans-serif;">{{ $line }}</p>
@endforeach
</td>
</tr>
</table>
@endif
