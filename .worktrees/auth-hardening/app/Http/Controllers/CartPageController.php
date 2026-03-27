<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartPageController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {
    }

    public function index(Request $request): Response
    {
        $cart = $this->cartService->getOrCreateForUser($request->user()->id);
        $cart->load('items.product.category', 'items.product.tags');

        return Inertia::render('Customer/Cart', [
            'cart' => $this->formatCart($cart, $request),
        ]);
    }

    private function formatCart(Cart $cart, Request $request): array
    {
        $subtotal = (float) $cart->items->sum(fn ($item) => ($item->product?->price ?? 0) * $item->quantity);
        $tax = round($subtotal * 0.1, 2);
        $shippingCost = 0;

        return [
            'id' => $cart->id,
            'items' => $cart->items->map(fn ($item) => [
                'id' => $item->id,
                'product' => (new ProductResource($item->product))->toArray($request),
                'quantity' => (int) $item->quantity,
            ])->toArray(),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_cost' => $shippingCost,
            'total' => $subtotal + $tax + $shippingCost,
            'item_count' => $cart->items->count(),
        ];
    }

    public function addItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->cartService->addItem(
            $request->user()->id,
            $validated['product_id'],
            $validated['quantity'],
        );

        return back()->with('success', 'Produto adicionado ao carrinho!');
    }

    public function updateItem(Request $request, int $item): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartItem = $this->cartService->findItemById($item);

        if (! $cartItem || $cartItem->cart->user_id !== $request->user()->id) {
            abort(404);
        }

        $this->cartService->updateItem($cartItem, $validated['quantity']);

        return back()->with('success', 'Carrinho atualizado!');
    }

    public function removeItem(Request $request, int $item): RedirectResponse
    {
        $cartItem = $this->cartService->findItemById($item);

        if (! $cartItem || $cartItem->cart->user_id !== $request->user()->id) {
            abort(404);
        }

        $this->cartService->removeItem($cartItem);

        return back()->with('success', 'Item removido do carrinho!');
    }

    public function clear(Request $request): RedirectResponse
    {
        $this->cartService->clear($request->user()->id);

        return back()->with('success', 'Carrinho limpo!');
    }
}
