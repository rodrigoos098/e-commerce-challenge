<x-mail::message>
# Pedido cancelado

Ola, **{{ $order->user->name }}**!

Seu pedido foi cancelado. Se precisar, voce pode revisar os itens e criar uma nova compra a qualquer momento.

@include('emails.orders.partials.order-details', ['order' => $order])

Obrigado,
{{ config('app.name') }}
</x-mail::message>
