<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\OrderDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Requests\Api\V1\UpdateOrderStatusRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    /**
     * List orders for the authenticated user (or all orders for admin).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        if ($request->user()->hasRole('admin')) {
            $filters = $request->only(['status', 'user_id']);
            $orders = $this->orderService->paginate($filters, $perPage);
        } else {
            $orders = $this->orderService->paginateForUser($request->user()->id, $perPage);
        }

        return $this->paginatedResponse(OrderResource::collection($orders));
    }

    /**
     * Display a specific order.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if ($request->user()->hasRole('admin')) {
            $order = $this->orderService->findById($id);
        } else {
            $order = $this->orderService->findByIdForUser($id, $request->user()->id);
        }

        if (! $order) {
            return $this->notFoundResponse('Order not found.');
        }

        return $this->successResponse(new OrderResource($order));
    }

    /**
     * Create a new order from the user's cart.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createFromCart(OrderDTO::fromRequest($request));

        return $this->createdResponse(new OrderResource($order));
    }

    /**
     * Update the status of an order (admin only).
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $updated = $this->orderService->updateStatus($order, $request->string('status')->toString());

        return $this->successResponse(new OrderResource($updated));
    }
}
