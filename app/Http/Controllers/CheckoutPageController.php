<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Address;
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

        $addresses = $request->user()->addresses()
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('is_default_billing')
            ->latest()
            ->get();
        $shippingZipCode = $this->cartTotalsService->resolveShippingZipCode(
            preferredZipCode: $request->input('shipping_mode') === 'saved'
                ? (string) $addresses->firstWhere('id', (int) $request->integer('shipping_address_id'))?->zip_code
                : (string) $request->input('shipping_zip', ''),
            addresses: $addresses,
            fallbackToAddresses: $request->input('shipping_mode', 'saved') === 'saved',
        );
        $totals = $this->cartTotalsService->calculate($cart->items, $shippingZipCode);

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
                'shipping_zip_code' => $totals['shipping_zip_code'],
                'shipping_rule_label' => $totals['shipping_rule_label'],
                'shipping_rule_description' => $totals['shipping_rule_description'],
                'shipping_is_free' => $totals['shipping_is_free'],
                'item_count' => $cart->items->count(),
            ],
            'addresses' => $addresses->map(fn (Address $address): array => [
                'id' => $address->id,
                'label' => $address->label,
                'recipient_name' => $address->recipient_name,
                'street' => $address->street,
                'city' => $address->city,
                'state' => $address->state,
                'zip_code' => $address->zip_code,
                'country' => $address->country,
                'is_default_shipping' => $address->is_default_shipping,
                'is_default_billing' => $address->is_default_billing,
            ])->values()->all(),
        ]);
    }
}
