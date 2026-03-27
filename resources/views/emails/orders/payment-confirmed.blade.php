<x-mail::message>
# Pagamento confirmado! 💫

Olá, **{{ $order->user->name }}**!

Ótima notícia — recebemos a confirmação do pagamento do seu pedido. Agora nossa equipe começa a preparar tudo com o cuidado que cada peça merece.

@include('emails.orders.partials.order-details', ['order' => $order])

<x-mail::button :url="config('app.url')" color="primary">
Ver detalhes do pedido
</x-mail::button>

<x-mail::panel>
Quando seu pedido for despachado, você receberá um email com as informações de rastreio. Cada etapa cuidadosamente acompanhada.
</x-mail::panel>

Com carinho,<br>
**Equipe {{ config('app.name') }}**
</x-mail::message>
