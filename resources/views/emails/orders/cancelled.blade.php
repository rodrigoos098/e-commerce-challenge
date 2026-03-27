<x-mail::message>
# Pedido cancelado

Olá, **{{ $order->user->name }}**,

Informamos que seu pedido foi cancelado. Sabemos que isso pode ser frustrante — se houve algum problema, estamos aqui para ajudar.

@include('emails.orders.partials.order-details', ['order' => $order])

<x-mail::button :url="config('app.url')" color="primary">
Voltar à loja
</x-mail::button>

<x-mail::panel>
Se o cancelamento não foi intencional ou se precisar de ajuda, entre em contato conosco. Estamos sempre disponíveis para cuidar de você.
</x-mail::panel>

Com carinho,<br>
**Equipe {{ config('app.name') }}**
</x-mail::message>
