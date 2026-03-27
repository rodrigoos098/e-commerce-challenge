<x-mail::message>
# Confirmacao do pedido

Ola, **{{ $order->user->name }}**!

Recebemos o seu pedido com sucesso e iniciamos a preparacao para as proximas etapas.

@include('emails.orders.partials.order-details', ['order' => $order])

Obrigado,
{{ config('app.name') }}
</x-mail::message>
