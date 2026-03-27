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
        $items = $cart->items->map(function ($item) use ($request): array {
            return [
                'id' => $item->id,
                'product' => (new ProductResource($item->product))->toArray($request),
                'quantity' => (int) $item->quantity,
            ];
        });
        $stockIssues = $items
            ->map(function (array $item): ?array {
                $product = $item['product'];
                $availableQuantity = (int) ($product['quantity'] ?? 0);
                $requestedQuantity = (int) $item['quantity'];

                if (! ($product['active'] ?? false)) {
                    return [
                        'item_id' => $item['id'],
                        'product_id' => $product['id'],
                        'product_name' => $product['name'],
                        'requested_quantity' => $requestedQuantity,
                        'available_quantity' => 0,
                        'reason' => 'unavailable',
                        'message' => "O produto {$product['name']} nao esta mais disponivel para compra.",
                    ];
                }

                if (! ($product['in_stock'] ?? false) || $availableQuantity === 0) {
                    return [
                        'item_id' => $item['id'],
                        'product_id' => $product['id'],
                        'product_name' => $product['name'],
                        'requested_quantity' => $requestedQuantity,
                        'available_quantity' => 0,
                        'reason' => 'out_of_stock',
                        'message' => "O produto {$product['name']} esgotou e precisa ser removido do carrinho.",
                    ];
                }

                if ($availableQuantity < $requestedQuantity) {
                    return [
                        'item_id' => $item['id'],
                        'product_id' => $product['id'],
                        'product_name' => $product['name'],
                        'requested_quantity' => $requestedQuantity,
                        'available_quantity' => $availableQuantity,
                        'reason' => 'insufficient_stock',
                        'message' => "O produto {$product['name']} tem apenas {$availableQuantity} unidade(s) disponivel(is). Ajuste a quantidade para continuar.",
                    ];
                }

                return null;
            })
            ->filter()
            ->values();

        return Inertia::render('Customer/Checkout', [
            'cart' => [
                'id' => $cart->id,
                'items' => $items->values()->all(),
                'subtotal' => $totals['subtotal'],
                'tax' => $totals['tax'],
                'shipping_cost' => $totals['shipping_cost'],
                'total' => $totals['total'],
                'shipping_zip_code' => $totals['shipping_zip_code'],
                'shipping_rule_label' => $totals['shipping_rule_label'],
                'shipping_rule_description' => $totals['shipping_rule_description'],
                'shipping_is_free' => $totals['shipping_is_free'],
                'item_count' => $cart->items->count(),
                'stock_check' => [
                    'has_issues' => $stockIssues->isNotEmpty(),
                    'message' => $stockIssues->count() === 1
                        ? $stockIssues->first()['message']
                        : 'Alguns itens do seu carrinho ficaram indisponiveis ou com estoque insuficiente. Revise o carrinho antes de concluir o pagamento.',
                    'issues' => $stockIssues->all(),
                ],
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
