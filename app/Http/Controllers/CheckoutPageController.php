<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\ProductResource;
use App\Services\CartService;
use App\Services\CartTotalsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutPageController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CartTotalsService $cartTotalsService,
    ) {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $cart = $this->cartService->getOrCreateForUser($request->user()->id);
        $cart->load('items.product.category', 'items.product.tags');

        if ($cart->items->isEmpty()) {
            return redirect('/cart')->with('error', 'Seu carrinho está vazio.');
        }

        $totals = $this->cartTotalsService->calculate($cart->items);

        return Inertia::render('Customer/Checkout', [
            'cart' => [
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
                'item_count' => $cart->items->count(),
            ],
        ]);
    }
}
