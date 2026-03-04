<?php

namespace App\Http\Controllers;

use App\DTOs\OrderDTO;
use App\Http\Resources\Api\V1\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderPageController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    public function index(Request $request): Response
    {
        $perPage = (int) $request->input('per_page', 15);
        $orders = $this->orderService->paginateForUser($request->user()->id, $perPage);

        return Inertia::render('Customer/Orders/Index', [
            'orders' => (OrderResource::collection($orders))->response()->getData(true),
        ]);
    }

    public function show(Request $request, int $order): Response
    {
        $orderModel = $this->orderService->findByIdForUser($order, $request->user()->id);

        if (! $orderModel) {
            abort(404);
        }

        return Inertia::render('Customer/Orders/Show', [
            'order' => (new OrderResource($orderModel))->resolve($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_street' => ['required', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:255'],
            'shipping_state' => ['required', 'string', 'max:255'],
            'shipping_zip' => ['required', 'string', 'max:20'],
            'shipping_country' => ['required', 'string', 'max:255'],
        ]);

        $shippingAddress = [
            'name' => $request->input('shipping_name'),
            'street' => $request->input('shipping_street'),
            'city' => $request->input('shipping_city'),
            'state' => $request->input('shipping_state'),
            'zip_code' => $request->input('shipping_zip'),
            'country' => $request->input('shipping_country'),
        ];

        if ($request->boolean('same_billing')) {
            $billingAddress = $shippingAddress;
        } else {
            $billingAddress = [
                'name' => $request->input('billing_name', $shippingAddress['name']),
                'street' => $request->input('billing_street', $shippingAddress['street']),
                'city' => $request->input('billing_city', $shippingAddress['city']),
                'state' => $request->input('billing_state', $shippingAddress['state']),
                'zip_code' => $request->input('billing_zip', $shippingAddress['zip_code']),
                'country' => $request->input('billing_country', $shippingAddress['country']),
            ];
        }

        $dto = new OrderDTO(
            userId: $request->user()->id,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            notes: $request->input('notes'),
        );

        $order = $this->orderService->createFromCart($dto);

        return redirect("/customer/orders/{$order->id}")->with('success', 'Pedido criado com sucesso!');
    }
}
