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
    ) {
    }

    /**
     * List orders for the authenticated user (or all orders for admin).
     *
     * @OA\Get(
     *     path="/orders",
     *     summary="Listar pedidos",
     *     tags={"Pedidos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", description="Filtrar por status", @OA\Schema(type="string", enum={"pending","processing","shipped","delivered","cancelled"})),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Lista paginada de pedidos"),
     *     @OA\Response(response=401, description="Não autenticado")
     * )
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
     *
     * @OA\Get(
     *     path="/orders/{id}",
     *     summary="Exibir pedido",
     *     tags={"Pedidos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalhes do pedido com itens"),
     *     @OA\Response(response=404, description="Pedido não encontrado")
     * )
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
     *
     * @OA\Post(
     *     path="/orders",
     *     summary="Criar pedido a partir do carrinho",
     *     tags={"Pedidos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"shipping_address","billing_address"},
     *             @OA\Property(property="shipping_address", type="object",
     *                 @OA\Property(property="street", type="string"),
     *                 @OA\Property(property="city", type="string"),
     *                 @OA\Property(property="state", type="string"),
     *                 @OA\Property(property="zip", type="string"),
     *                 @OA\Property(property="country", type="string")
     *             ),
     *             @OA\Property(property="billing_address", type="object"),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Pedido criado com sucesso"),
     *     @OA\Response(response=422, description="Carrinho vazio ou estoque insuficiente", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createFromCart(OrderDTO::fromRequest($request));

        return $this->createdResponse(new OrderResource($order));
    }

    /**
     * Update the status of an order (admin only).
     *
     * @OA\Put(
     *     path="/orders/{id}/status",
     *     summary="Atualizar status do pedido (admin)",
     *     tags={"Pedidos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending","processing","shipped","delivered","cancelled"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status atualizado com sucesso"),
     *     @OA\Response(response=403, description="Acesso negado"),
     *     @OA\Response(response=404, description="Pedido não encontrado")
     * )
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $updated = $this->orderService->updateStatus($order, $request->string('status')->toString());

        return $this->successResponse(new OrderResource($updated));
    }
}
