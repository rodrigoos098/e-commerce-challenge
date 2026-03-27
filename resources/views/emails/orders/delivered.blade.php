<x-mail::message>
# Pedido entregue! 🎁

Olá, **{{ $order->user->name }}**!

Seu pedido foi marcado como entregue. Esperamos que cada peça traga a mesma alegria com que foi preparada para você.

@include('emails.orders.partials.order-details', ['order' => $order])

<x-mail::button :url="config('app.url')" color="primary">
Explorar mais presentes
</x-mail::button>

<x-mail::panel>
Gostou da experiência? Ficaremos felizes em saber sua opinião. Cada feedback nos ajuda a cuidar ainda melhor de quem confia na Shopsugi.
</x-mail::panel>

Com carinho,<br>
**Equipe {{ config('app.name') }}**
</x-mail::message>
