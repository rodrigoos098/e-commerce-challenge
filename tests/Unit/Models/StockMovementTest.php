<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    // ── Fillable ──────────────────────────────────────────────────────────────

    public function test_fillable_attributes_are_correct(): void
    {
        $expected = [
            'product_id', 'type', 'quantity', 'reason',
            'reference_type', 'reference_id',
        ];

        $stockMovement = new StockMovement;

        $this->assertEquals($expected, $stockMovement->getFillable());
    }

    // ── Casts ─────────────────────────────────────────────────────────────────

    public function test_quantity_is_cast_to_integer(): void
    {
        $stockMovement = StockMovement::factory()->create(['quantity' => '15']);

        $this->assertIsInt($stockMovement->quantity);
        $this->assertEquals(15, $stockMovement->quantity);
    }

    // ── Types constants ───────────────────────────────────────────────────────

    public function test_types_constant_contains_expected_values(): void
    {
        $expected = ['entrada', 'saida', 'ajuste', 'venda', 'devolucao'];

        $this->assertEquals($expected, StockMovement::TYPES);
    }

    public function test_stock_movement_can_be_created_with_each_type(): void
    {
        foreach (StockMovement::TYPES as $type) {
            $movement = StockMovement::factory()->create(['type' => $type]);

            $this->assertEquals($type, $movement->type);
        }
    }

    // ── Relacionamentos ───────────────────────────────────────────────────────

    public function test_product_relationship_returns_belongs_to(): void
    {
        $stockMovement = new StockMovement;

        $this->assertInstanceOf(BelongsTo::class, $stockMovement->product());
    }

    public function test_product_relationship_returns_product_model(): void
    {
        $stockMovement = StockMovement::factory()->create();

        $this->assertInstanceOf(Product::class, $stockMovement->product);
    }

    public function test_product_relationship_returns_correct_product(): void
    {
        $product = Product::factory()->create();
        $stockMovement = StockMovement::factory()->create(['product_id' => $product->id]);

        $this->assertEquals($product->id, $stockMovement->product->id);
    }

    // ── Comportamento ─────────────────────────────────────────────────────────

    public function test_multiple_stock_movements_can_belong_to_same_product(): void
    {
        $product = Product::factory()->create();

        StockMovement::factory()->count(3)->create(['product_id' => $product->id]);

        $this->assertCount(3, $product->stockMovements);
    }

    public function test_reason_is_optional(): void
    {
        $stockMovement = StockMovement::factory()->create(['reason' => null]);

        $this->assertNull($stockMovement->reason);
    }

    public function test_reference_type_and_reference_id_are_optional(): void
    {
        $stockMovement = StockMovement::factory()->create([
            'reference_type' => null,
            'reference_id' => null,
        ]);

        $this->assertNull($stockMovement->reference_type);
        $this->assertNull($stockMovement->reference_id);
    }
}
