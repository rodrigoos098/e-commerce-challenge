<x-mail::message>
# Pagamento confirmado! 💫

Olá, **{{ $order->user->name }}**!

Ótima notícia — recebemos a confirmação do pagamento do seu pedido. Agora nossa equipe começa a preparar tudo com o cuidado que cada peça merece.

@include('emails.orders.partials.order-details', ['order' => $order])

<x-mail::button :url="route('orders.show', $order)" color="primary">
Ver detalhes do pedido
</x-mail::button>

<x-mail::panel>
Quando seu pedido for despachado, você receberá um novo email com atualização. Cada etapa cuidadosamente acompanhada.
</x-mail::panel>

Com carinho,<br>
**Equipe {{ config('app.name') }}**
</x-mail::message>
