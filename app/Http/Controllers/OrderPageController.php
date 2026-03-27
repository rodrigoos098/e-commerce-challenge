<?php

namespace App\Http\Controllers;

use App\DTOs\OrderDTO;
use App\Http\Requests\Web\StoreOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OrderPageController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $perPage = (int) $request->input('per_page', 15);
        $orders = $this->orderService->paginateForUser($request->user()->id, $perPage);

        return Inertia::render('Customer/Orders/Index', [
            'orders' => (OrderResource::collection($orders))->response()->getData(true),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $this->authorize('view', $order);

        $order->loadMissing('items.product');

        return Inertia::render('Customer/Orders/Show', [
            'order' => (new OrderResource($order))->resolve($request),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $dto = new OrderDTO(
            userId: $request->user()->id,
            shippingAddress: $request->shippingAddressSnapshot(),
            billingAddress: $request->billingAddressSnapshot(),
            notes: $request->validated('notes'),
            paymentSimulated: $request->boolean('payment_simulated'),
        );

        try {
            $order = $this->orderService->createFromCart($dto);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'order' => 'Nao foi possivel finalizar o pedido agora. Tente novamente em instantes.',
            ]);
        }

        return redirect("/customer/orders/{$order->id}")->with('success', 'Pagamento simulado com sucesso e pedido criado.');
    }

    public function cancel(Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $this->orderService->cancel($order);

        return back()->with('success', 'Pedido cancelado com sucesso!');
    }
}
