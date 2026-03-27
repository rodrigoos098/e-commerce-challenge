<x-mail::message>
# Seu pedido está a caminho! 📦

Olá, **{{ $order->user->name }}**!

Boas novas — seu pedido foi despachado e já está a caminho do endereço informado. Cada peça foi embalada com o cuidado que merece.

@include('emails.orders.partials.order-details', ['order' => $order])

<x-mail::button :url="config('app.url')" color="primary">
Rastrear pedido
</x-mail::button>

<x-mail::panel>
A entrega pode levar alguns dias úteis. Assim que for entregue, avisaremos você por email.
</x-mail::panel>

Com carinho,<br>
**Equipe {{ config('app.name') }}**
</x-mail::message>
