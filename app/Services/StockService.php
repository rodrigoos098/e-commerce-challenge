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
use Illuminate\Support\Facades\Cache;
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
     * Decrease stock for a locked product inside the checkout transaction.
     */
    public function decreaseStockForLockedProduct(Product $product, int $quantity, int $orderId): StockMovement
    {
        $movement = $this->stockMovementRepository->create([
            'product_id' => $product->id,
            'type' => 'venda',
            'quantity' => $quantity,
            'reason' => 'Stock decreased by order',
            'reference_type' => 'order',
            'reference_id' => $orderId,
        ]);

        $newQuantity = max(0, $product->quantity - $quantity);
        $this->productRepository->update($product, ['quantity' => $newQuantity]);
        $product->quantity = $newQuantity;
        $this->invalidateProductCache();

        if ($newQuantity <= $product->min_quantity) {
            $freshProduct = $this->productRepository->findById($product->id);

            if ($freshProduct) {
                event(new StockLow($freshProduct));
            }
        }

        $this->logActivity('stock', 'Stock movement recorded', [
            'movement_id' => $movement->id,
            'product_id' => $product->id,
            'type' => 'venda',
            'quantity' => $quantity,
            'reason' => 'Stock decreased by order',
            'reference_type' => 'order',
            'reference_id' => $orderId,
        ]);

        return $movement;
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
     * Adjust product stock to a target quantity while preserving movement history.
     */
    public function adjustStock(int $productId, int $targetQuantity, string $reason): StockMovement
    {
        $dto = new StockMovementDTO(
            productId: $productId,
            type: 'ajuste',
            quantity: $targetQuantity,
            reason: $reason,
            referenceType: 'manual_adjustment',
        );

        return $this->recordMovement($dto);
    }

    /**
     * Restore product stock after an order cancellation.
     */
    public function restoreStockFromCancelledOrder(int $productId, int $quantity, int $orderId): StockMovement
    {
        $dto = new StockMovementDTO(
            productId: $productId,
            type: 'devolucao',
            quantity: $quantity,
            reason: 'Stock restored after order cancellation',
            referenceType: 'order',
            referenceId: $orderId,
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
        $this->invalidateProductCache();

        if ($newQuantity <= $product->min_quantity) {
            $freshProduct = $this->productRepository->findById($product->id);

            if ($freshProduct) {
                event(new StockLow($freshProduct));
            }
        }
    }

    /**
     * Flush cached product payloads after stock changes.
     */
    private function invalidateProductCache(): void
    {
        Cache::tags(['products'])->flush();
    }
}
