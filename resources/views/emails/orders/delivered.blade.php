<x-mail::message>
# Pedido entregue

Ola, **{{ $order->user->name }}**!

Seu pedido foi marcado como entregue. Esperamos que voce aproveite sua compra.

@include('emails.orders.partials.order-details', ['order' => $order])

Obrigado,
{{ config('app.name') }}
</x-mail::message>
