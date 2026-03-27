<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminOrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'user_id']);
        $perPage = (int) $request->input('per_page', 15);

        $orders = $this->orderService->paginate($filters, $perPage);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => [
                'data' => $orders->items() ? collect($orders->items())->map(fn ($order) => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'total' => (float) $order->total,
                    'created_at' => $order->created_at->toISOString(),
                    'user' => $order->user ? [
                        'id' => $order->user->id,
                        'name' => $order->user->name,
                        'email' => $order->user->email,
                    ] : null,
                ])->toArray() : [],
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ],
            ],
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $order->load('user', 'items.product');

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'paid_at' => $order->paid_at?->toIso8601String(),
                'total' => (float) $order->total,
                'subtotal' => (float) $order->subtotal,
                'shipping_cost' => (float) $order->shipping_cost,
                'notes' => $order->notes,
                'created_at' => $order->created_at->toISOString(),
                'updated_at' => $order->updated_at->toISOString(),
                'user' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ] : null,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product?->name ?? 'Produto removido',
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                ])->toArray(),
                'shipping_address' => $order->shipping_address,
                'billing_address' => $order->billing_address,
            ],
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();

        $this->orderService->updateStatus($order, $validated['status']);

        return back()->with('success', 'Status do pedido atualizado com sucesso!');
    }
}
