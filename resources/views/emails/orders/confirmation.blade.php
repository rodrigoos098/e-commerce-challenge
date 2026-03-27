<x-mail::message>
# Recebemos seu pedido! ✨

Olá, **{{ $order->user->name }}**!

Que alegria ter você conosco. Seu pedido foi recebido com carinho e já estamos cuidando de cada detalhe para que tudo chegue perfeito até você.

@include('emails.orders.partials.order-details', ['order' => $order])

<x-mail::button :url="config('app.url')" color="primary">
Acompanhar meu pedido
</x-mail::button>

<x-mail::panel>
Próximos passos: assim que o pagamento for confirmado, enviaremos uma atualização. Fique tranquilo — estamos cuidando de tudo.
</x-mail::panel>

Com carinho,<br>
**Equipe {{ config('app.name') }}**
</x-mail::message>
