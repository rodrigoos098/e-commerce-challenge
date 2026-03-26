<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\ProductResource;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutPageController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $cart = $this->cartService->getOrCreateForUser($request->user()->id);
        $cart->load('items.product.category', 'items.product.tags');

        if ($cart->items->isEmpty()) {
            return redirect('/cart')->with('error', 'Seu carrinho está vazio.');
        }

        $subtotal = (float) $cart->items->sum(fn ($item) => ($item->product?->price ?? 0) * $item->quantity);
        $tax = round($subtotal * 0.1, 2);
        $shippingCost = 0;

        return Inertia::render('Customer/Checkout', [
            'cart' => [
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
            ],
        ]);
    }
}
