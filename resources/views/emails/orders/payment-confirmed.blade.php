<x-mail::message>
# Pagamento confirmado

Ola, **{{ $order->user->name }}**!

Confirmamos o pagamento do seu pedido. Agora seguimos com a preparacao da entrega.

@include('emails.orders.partials.order-details', ['order' => $order])

Obrigado,
{{ config('app.name') }}
</x-mail::message>
