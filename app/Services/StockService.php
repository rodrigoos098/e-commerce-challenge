<?php

namespace App\Services;

use App\DTOs\StockMovementDTO;
use App\Events\StockLow;
use App\Models\Product;
use App\Models\StockMovement;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StockService
{
    use LogsActivity;

    public function __construct(
        private readonly StockMovementRepositoryInterface $stockMovementRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {
    }

    /**
     * Get paginated stock movements for a product.
     */
    public function paginateForProduct(int $productId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->stockMovementRepository->paginateForProduct($productId, $perPage);
    }

    /**
     * Record a stock movement and update product quantity.
     */
    public function recordMovement(StockMovementDTO $dto): StockMovement
    {
        $movement = DB::transaction(function () use ($dto): StockMovement {
            $movement = $this->stockMovementRepository->create($dto->toArray());

            $product = $this->productRepository->findById($dto->productId);

            if ($product) {
                $this->applyMovementToProduct($product, $dto->type, $dto->quantity);
            }

            return $movement;
        });

        $this->logActivity('stock', 'Stock movement recorded', [
            'movement_id' => $movement->id,
            'product_id' => $dto->productId,
            'type' => $dto->type,
            'quantity' => $dto->quantity,
            'reason' => $dto->reason,
            'reference_type' => $dto->referenceType,
            'reference_id' => $dto->referenceId,
        ]);

        return $movement;
    }

    /**
     * Decrease product stock after an order (venda type).
     */
    public function decreaseStock(int $productId, int $quantity, int $orderId): StockMovement
    {
        $dto = new StockMovementDTO(
            productId: $productId,
            type: 'venda',
            quantity: $quantity,
            reason: 'Stock decreased by order',
            referenceType: 'order',
            referenceId: $orderId,
        );

        return $this->recordMovement($dto);
    }

    /**
     * Increase product stock (entrada type).
     */
    public function increaseStock(int $productId, int $quantity, string $reason): StockMovement
    {
        $dto = new StockMovementDTO(
            productId: $productId,
            type: 'entrada',
            quantity: $quantity,
            reason: $reason,
        );

        return $this->recordMovement($dto);
    }

    /**
     * Apply a stock movement to a product's quantity.
     */
    private function applyMovementToProduct(Product $product, string $type, int $quantity): void
    {
        $newQuantity = match ($type) {
            'entrada', 'devolucao' => $product->quantity + $quantity,
            'saida', 'venda' => max(0, $product->quantity - $quantity),
            'ajuste' => $quantity,
            default => $product->quantity,
        };

        $this->productRepository->update($product, ['quantity' => $newQuantity]);

        if ($newQuantity <= $product->min_quantity) {
            $freshProduct = $this->productRepository->findById($product->id);

            if ($freshProduct) {
                event(new StockLow($freshProduct));
            }
        }
    }
}
