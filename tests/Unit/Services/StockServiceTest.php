<?php

namespace Tests\Unit\Services;

use App\DTOs\StockMovementDTO;
use App\Events\StockLow;
use App\Models\Product;
use App\Models\StockMovement;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(
        ?StockMovementRepositoryInterface $movementRepo = null,
        ?ProductRepositoryInterface $productRepo = null,
    ): StockService {
        $movementRepo ??= Mockery::mock(StockMovementRepositoryInterface::class);
        $productRepo ??= Mockery::mock(ProductRepositoryInterface::class);

        return new StockService($movementRepo, $productRepo);
    }

    // ── RecordMovement ────────────────────────────────────────────────────────

    public function test_record_movement_creates_movement_and_updates_product(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 20, 'min_quantity' => 5]);
        $movement = StockMovement::factory()->make(['id' => 1, 'type' => 'entrada', 'quantity' => 10]);

        $movementRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $movementRepo->shouldReceive('create')->once()->andReturn($movement);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(1)->andReturn($product);
        $productRepo->shouldReceive('update')->once()->with($product, ['quantity' => 30]); // 20 + 10
        $productRepo->shouldReceive('findById')->andReturn($product);

        $dto = new StockMovementDTO(productId: 1, type: 'entrada', quantity: 10, reason: 'Restock');

        $result = $this->makeService($movementRepo, $productRepo)->recordMovement($dto);

        $this->assertSame($movement, $result);
    }

    // ── ApplyMovement — type logic ─────────────────────────────────────────────

    public function test_entrada_type_increases_stock(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 10, 'min_quantity' => 3]);
        $movement = StockMovement::factory()->make(['type' => 'entrada', 'quantity' => 5]);

        $movementRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $movementRepo->shouldReceive('create')->andReturn($movement);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(1)->andReturn($product);
        $productRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($p, $data) => $data['quantity'] === 15); // 10 + 5
        $productRepo->shouldReceive('findById')->andReturn($product);

        $dto = new StockMovementDTO(productId: 1, type: 'entrada', quantity: 5, reason: '');

        $this->makeService($movementRepo, $productRepo)->recordMovement($dto);
    }

    public function test_saida_type_decreases_stock(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 10, 'min_quantity' => 3]);
        $movement = StockMovement::factory()->make(['type' => 'saida', 'quantity' => 4]);

        $movementRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $movementRepo->shouldReceive('create')->andReturn($movement);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(1)->andReturn($product);
        $productRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($p, $data) => $data['quantity'] === 6); // 10 - 4
        $productRepo->shouldReceive('findById')->andReturn($product);

        $dto = new StockMovementDTO(productId: 1, type: 'saida', quantity: 4, reason: '');

        $this->makeService($movementRepo, $productRepo)->recordMovement($dto);
    }

    public function test_venda_type_decreases_stock(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 10, 'min_quantity' => 3]);
        $movement = StockMovement::factory()->make(['type' => 'venda', 'quantity' => 3]);

        $movementRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $movementRepo->shouldReceive('create')->andReturn($movement);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(1)->andReturn($product);
        $productRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($p, $data) => $data['quantity'] === 7); // 10 - 3
        $productRepo->shouldReceive('findById')->andReturn($product);

        $dto = new StockMovementDTO(productId: 1, type: 'venda', quantity: 3, reason: '');

        $this->makeService($movementRepo, $productRepo)->recordMovement($dto);
    }

    public function test_ajuste_type_sets_exact_quantity(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 100, 'min_quantity' => 5]);
        $movement = StockMovement::factory()->make(['type' => 'ajuste', 'quantity' => 25]);

        $movementRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $movementRepo->shouldReceive('create')->andReturn($movement);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(1)->andReturn($product);
        $productRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($p, $data) => $data['quantity'] === 25); // set to 25
        $productRepo->shouldReceive('findById')->andReturn($product);

        $dto = new StockMovementDTO(productId: 1, type: 'ajuste', quantity: 25, reason: '');

        $this->makeService($movementRepo, $productRepo)->recordMovement($dto);
    }

    public function test_devolucao_type_increases_stock(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 5, 'min_quantity' => 2]);
        $movement = StockMovement::factory()->make(['type' => 'devolucao', 'quantity' => 2]);

        $movementRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $movementRepo->shouldReceive('create')->andReturn($movement);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(1)->andReturn($product);
        $productRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($p, $data) => $data['quantity'] === 7); // 5 + 2
        $productRepo->shouldReceive('findById')->andReturn($product);

        $dto = new StockMovementDTO(productId: 1, type: 'devolucao', quantity: 2, reason: '');

        $this->makeService($movementRepo, $productRepo)->recordMovement($dto);
    }

    public function test_saida_does_not_go_below_zero(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 3, 'min_quantity' => 5]);
        $movement = StockMovement::factory()->make(['type' => 'saida', 'quantity' => 10]);

        $movementRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $movementRepo->shouldReceive('create')->andReturn($movement);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(1)->andReturn($product);
        $productRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($p, $data) => $data['quantity'] === 0); // max(0, 3-10) = 0
        $productRepo->shouldReceive('findById')->andReturn($product);

        $dto = new StockMovementDTO(productId: 1, type: 'saida', quantity: 10, reason: '');

        $this->makeService($movementRepo, $productRepo)->recordMovement($dto);
    }

    // ── StockLow Event ────────────────────────────────────────────────────────

    public function test_stock_low_event_is_fired_when_new_quantity_lte_min(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 10, 'min_quantity' => 5]);
        $freshProduct = Product::factory()->make(['id' => 1, 'quantity' => 3, 'min_quantity' => 5]);
        $movement = StockMovement::factory()->make(['type' => 'saida', 'quantity' => 7]);

        $movementRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $movementRepo->shouldReceive('create')->andReturn($movement);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(1)->andReturn($product);
        $productRepo->shouldReceive('update')->andReturn($product);
        $productRepo->shouldReceive('findById')->andReturn($freshProduct); // after update

        $dto = new StockMovementDTO(productId: 1, type: 'saida', quantity: 7, reason: '');

        $this->makeService($movementRepo, $productRepo)->recordMovement($dto);

        Event::assertDispatched(StockLow::class);
    }

    // ── DecreaseStock / IncreaseStock ─────────────────────────────────────────

    public function test_decrease_stock_creates_venda_movement(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 10, 'min_quantity' => 3]);
        $movement = StockMovement::factory()->make(['type' => 'venda', 'quantity' => 2]);

        $movementRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $movementRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['type'] === 'venda' && $data['quantity'] === 2)
            ->andReturn($movement);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(1)->andReturn($product);
        $productRepo->shouldReceive('update')->andReturn($product);
        $productRepo->shouldReceive('findById')->andReturn($product);

        $this->makeService($movementRepo, $productRepo)->decreaseStock(1, 2, 99);
    }

    public function test_increase_stock_creates_entrada_movement(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 5, 'min_quantity' => 3]);
        $movement = StockMovement::factory()->make(['type' => 'entrada', 'quantity' => 20]);

        $movementRepo = Mockery::mock(StockMovementRepositoryInterface::class);
        $movementRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['type'] === 'entrada' && $data['quantity'] === 20)
            ->andReturn($movement);

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with(1)->andReturn($product);
        $productRepo->shouldReceive('update')->andReturn($product);
        $productRepo->shouldReceive('findById')->andReturn($product);

        $this->makeService($movementRepo, $productRepo)->increaseStock(1, 20, 'Restocking');
    }
}
