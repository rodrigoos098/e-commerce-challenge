<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\CartTotalsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartPageController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CartTotalsService $cartTotalsService,
    ) {
    }

    public function index(Request $request): Response
    {
        $cart = $this->cartService->getOrCreateForContext($request->user()?->id, $request->session()->getId());
        $cart->load('items.product.category', 'items.product.tags');

        if (! $request->user()) {
            $request->session()->put('guest_cart_id', $cart->id);
        }

        return Inertia::render('Customer/Cart', [
            'cart' => $this->formatCart($cart, $request),
        ]);
    }

    private function formatCart(Cart $cart, Request $request): array
    {
        $addresses = collect();

        if ($request->user()) {
            $addresses = $request->user()->addresses()
                ->orderByDesc('is_default_shipping')
                ->latest()
                ->get(['zip_code', 'is_default_shipping']);
        }

        $shippingZipCode = $this->cartTotalsService->resolveShippingZipCode(addresses: $addresses);

        $totals = $this->cartTotalsService->calculate($cart->items, $shippingZipCode);

        return [
            'id' => $cart->id,
            'items' => $cart->items->map(fn ($item) => [
                'id' => $item->id,
                'product' => (new ProductResource($item->product))->toArray($request),
                'quantity' => (int) $item->quantity,
            ])->toArray(),
            'subtotal' => $totals['subtotal'],
            'tax' => $totals['tax'],
            'shipping_cost' => $totals['shipping_cost'],
            'total' => $totals['total'],
            'shipping_zip_code' => $totals['shipping_zip_code'],
            'shipping_rule_label' => $totals['shipping_rule_label'],
            'shipping_rule_description' => $totals['shipping_rule_description'],
            'shipping_is_free' => $totals['shipping_is_free'],
            'item_count' => $cart->items->count(),
        ];
    }

    public function addItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartItem = $this->cartService->addItemForContext(
            $request->user()?->id,
            $request->session()->getId(),
            $validated['product_id'],
            $validated['quantity'],
        );

        if (! $request->user()) {
            $request->session()->put('guest_cart_id', $cartItem->cart_id);
        }

        return back()->with('success', 'Produto adicionado ao carrinho!');
    }

    public function updateItem(Request $request, int $item): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartItem = $this->cartService->findItemById($item);

        if (! $cartItem || ! $this->cartItemBelongsToRequest($cartItem, $request)) {
            abort(404);
        }

        $this->cartService->updateItem($cartItem, $validated['quantity']);

        return back()->with('success', 'Carrinho atualizado!');
    }

    public function removeItem(Request $request, int $item): RedirectResponse
    {
        $cartItem = $this->cartService->findItemById($item);

        if (! $cartItem || ! $this->cartItemBelongsToRequest($cartItem, $request)) {
            abort(404);
        }

        $this->cartService->removeItem($cartItem);

        return back()->with('success', 'Item removido do carrinho!');
    }

    public function clear(Request $request): RedirectResponse
    {
        $this->cartService->clearForContext($request->user()?->id, $request->session()->getId());
        $request->session()->forget('guest_cart_id');

        return back()->with('success', 'Carrinho limpo!');
    }

    private function cartItemBelongsToRequest(\App\Models\CartItem $cartItem, Request $request): bool
    {
        if ($request->user()) {
            return $cartItem->cart->user_id === $request->user()->id;
        }

        return $cartItem->cart->session_id === $request->session()->getId();
    }
}
