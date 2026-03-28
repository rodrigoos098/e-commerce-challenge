<x-mail::message>
# Pedido cancelado 💙

Olá, **{{ $order->user->name }}**,

Lamentamos muito informar que seu pedido foi cancelado. Se algo não correu como esperado, estamos aqui para ajudar.

@include('emails.orders.partials.order-details', ['order' => $order])

<x-mail::button :url="config('app.url')" color="error">
Voltar à loja
</x-mail::button>

<x-mail::panel>
Se o cancelamento não foi intencional ou se precisar de qualquer apoio, entre em contato com nossa equipe. Queremos garantir que tudo se resolva da melhor forma.
</x-mail::panel>

Com carinho,<br>
**Equipe {{ config('app.name') }}**
</x-mail::message>
