<x-mail::message>
# Pedido enviado

Ola, **{{ $order->user->name }}**!

Seu pedido foi enviado e esta a caminho do endereco informado.

@include('emails.orders.partials.order-details', ['order' => $order])

Obrigado,
{{ config('app.name') }}
</x-mail::message>
