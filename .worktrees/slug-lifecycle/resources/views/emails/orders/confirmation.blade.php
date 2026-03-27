<x-mail::message>
# Order Confirmation

Hi **{{ $order->user->name }}**,

Thank you for your order! We've received it and it's now being processed.

## Order Details

**Order #{{ $order->id }}**
Date: {{ $order->created_at->format('d/m/Y H:i') }}
Status: {{ ucfirst($order->status) }}

<x-mail::table>
| Product | Qty | Unit Price | Total |
|---------|-----|-----------|-------|
@foreach ($order->items as $item)
| {{ $item->product->name }} | {{ $item->quantity }} | R$ {{ number_format($item->unit_price, 2, ',', '.') }} | R$ {{ number_format($item->total_price, 2, ',', '.') }} |
@endforeach
</x-mail::table>

---

**Subtotal:** R$ {{ number_format($order->subtotal, 2, ',', '.') }}
**Tax (10%):** R$ {{ number_format($order->tax, 2, ',', '.') }}
**Shipping:** R$ {{ number_format($order->shipping_cost, 2, ',', '.') }}
**Total: R$ {{ number_format($order->total, 2, ',', '.') }}**

---

**Shipping Address**
{{ $order->shipping_address['street'] ?? '' }}, {{ $order->shipping_address['number'] ?? '' }}
{{ $order->shipping_address['city'] ?? '' }} — {{ $order->shipping_address['state'] ?? '' }}, {{ $order->shipping_address['zip_code'] ?? '' }}

Thanks,
{{ config('app.name') }}
</x-mail::message>
