<?php

namespace Tests\Unit\Services;

use App\DTOs\ProductDTO;
use App\Events\ProductCreated;
use App\Events\StockLow;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(ProductRepositoryInterface $repo): ProductService
    {
        return new ProductService($repo);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_create_fires_product_created_event(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 50, 'min_quantity' => 5]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('slugExists')->andReturn(false);
        $repo->shouldReceive('create')->once()->andReturn($product);
        $repo->shouldReceive('syncTags')->andReturn(null);
        $repo->shouldReceive('findById')->andReturn($product);

        $dto = new ProductDTO(name: 'Test', price: 99.9, quantity: 50, categoryId: 1);

        $this->makeService($repo)->create($dto);

        Event::assertDispatched(ProductCreated::class);
    }

    public function test_create_fires_stock_low_event_when_quantity_lte_min(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 2, 'min_quantity' => 10]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('slugExists')->andReturn(false);
        $repo->shouldReceive('create')->once()->andReturn($product);
        $repo->shouldReceive('syncTags')->andReturn(null);
        $repo->shouldReceive('findById')->andReturn($product);

        $dto = new ProductDTO(name: 'Low Stock Product', price: 10.0, quantity: 2, categoryId: 1);

        $this->makeService($repo)->create($dto);

        Event::assertDispatched(StockLow::class);
    }

    public function test_create_does_not_fire_stock_low_when_quantity_sufficient(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 50, 'min_quantity' => 5]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('slugExists')->andReturn(false);
        $repo->shouldReceive('create')->once()->andReturn($product);
        $repo->shouldReceive('syncTags')->andReturn(null);
        $repo->shouldReceive('findById')->andReturn($product);

        $dto = new ProductDTO(name: 'Full Stock', price: 10.0, quantity: 50, categoryId: 1);

        $this->makeService($repo)->create($dto);

        Event::assertNotDispatched(StockLow::class);
    }

    public function test_create_auto_generates_unique_slug(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 10, 'min_quantity' => 5]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        // First slug 'meu-produto' exists, 'meu-produto-1' doesn't
        $repo->shouldReceive('slugExists')->with('meu-produto', null)->once()->andReturn(true);
        $repo->shouldReceive('slugExists')->with('meu-produto-1', null)->once()->andReturn(false);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['slug'] === 'meu-produto-1')
            ->andReturn($product);
        $repo->shouldReceive('syncTags')->andReturn(null);
        $repo->shouldReceive('findById')->andReturn($product);

        $dto = new ProductDTO(name: 'Meu Produto', price: 10.0, quantity: 10, categoryId: 1);

        $this->makeService($repo)->create($dto);
    }

    public function test_create_uses_explicit_slug_as_unique_base(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 10, 'min_quantity' => 5]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('slugExists')->with('meu-slug-customizado', null)->once()->andReturn(false);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['slug'] === 'meu-slug-customizado')
            ->andReturn($product);
        $repo->shouldReceive('syncTags')->andReturn(null);
        $repo->shouldReceive('findById')->andReturn($product);

        $dto = new ProductDTO(
            name: 'Meu Produto',
            slug: 'Meu Slug Customizado',
            price: 10.0,
            quantity: 10,
            categoryId: 1,
        );

        $this->makeService($repo)->create($dto);
    }

    public function test_create_syncs_tags_when_tag_ids_provided(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 10, 'min_quantity' => 5]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('slugExists')->andReturn(false);
        $repo->shouldReceive('create')->once()->andReturn($product);
        $repo->shouldReceive('syncTags')->once()->with($product, [1, 2, 3]);
        $repo->shouldReceive('findById')->andReturn($product);

        $dto = new ProductDTO(name: 'Tagged', price: 10.0, quantity: 10, categoryId: 1, tagIds: [1, 2, 3]);

        $this->makeService($repo)->create($dto);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_update_fires_stock_low_event_when_new_quantity_low(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 2, 'min_quantity' => 10]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('update')->once()->andReturn($product);
        $repo->shouldReceive('syncTags')->andReturn(null);

        $original = Product::factory()->make(['id' => 1]);
        $dto = new ProductDTO(quantity: 2);

        $this->makeService($repo)->update($original, $dto);

        Event::assertDispatched(StockLow::class);
    }

    public function test_update_auto_generates_unique_slug_when_name_changes_without_explicit_slug(): void
    {
        Event::fake();

        $updatedProduct = Product::factory()->make(['id' => 1, 'quantity' => 10, 'min_quantity' => 5]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('slugExists')->with('novo-nome', 1)->once()->andReturn(true);
        $repo->shouldReceive('slugExists')->with('novo-nome-1', 1)->once()->andReturn(false);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(fn (Product $product, array $data) => $product->id === 1
                && $data['name'] === 'Novo Nome'
                && $data['slug'] === 'novo-nome-1')
            ->andReturn($updatedProduct);

        $original = Product::factory()->make(['id' => 1, 'name' => 'Nome Antigo']);
        $dto = new ProductDTO(name: 'Novo Nome');

        $this->makeService($repo)->update($original, $dto);
    }

    public function test_update_uses_explicit_slug_as_unique_base(): void
    {
        Event::fake();

        $updatedProduct = Product::factory()->make(['id' => 1, 'quantity' => 10, 'min_quantity' => 5]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('slugExists')->with('slug-manual', 1)->once()->andReturn(false);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(fn (Product $product, array $data) => $product->id === 1
                && $data['name'] === 'Novo Nome'
                && $data['slug'] === 'slug-manual')
            ->andReturn($updatedProduct);

        $original = Product::factory()->make(['id' => 1, 'name' => 'Nome Antigo']);
        $dto = new ProductDTO(name: 'Novo Nome', slug: 'Slug Manual');

        $this->makeService($repo)->update($original, $dto);
    }

    public function test_update_preserves_existing_slug_when_same_slug_is_explicitly_provided(): void
    {
        Event::fake();

        $updatedProduct = Product::factory()->make([
            'id' => 1,
            'name' => 'Novo Nome',
            'slug' => 'slug-estavel',
            'quantity' => 10,
            'min_quantity' => 5,
        ]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('slugExists')->with('slug-estavel', 1)->once()->andReturn(false);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(fn (Product $product, array $data) => $product->id === 1
                && $data['name'] === 'Novo Nome'
                && $data['slug'] === 'slug-estavel')
            ->andReturn($updatedProduct);

        $original = Product::factory()->make([
            'id' => 1,
            'name' => 'Nome Antigo',
            'slug' => 'slug-estavel',
        ]);
        $dto = new ProductDTO(name: 'Novo Nome', slug: 'slug-estavel');

        $this->makeService($repo)->update($original, $dto);
    }

    public function test_update_allows_clearing_explicit_nullable_cost_price(): void
    {
        Event::fake();

        $updatedProduct = Product::factory()->make([
            'id' => 1,
            'cost_price' => null,
            'quantity' => 10,
            'min_quantity' => 5,
        ]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(fn (Product $product, array $data) => $product->id === 1
                && array_key_exists('cost_price', $data)
                && $data['cost_price'] === null)
            ->andReturn($updatedProduct);

        $original = Product::factory()->make(['id' => 1, 'cost_price' => 99.90]);
        $dto = new ProductDTO(costPrice: null, presentFields: ['cost_price']);

        $this->makeService($repo)->update($original, $dto);
    }

    public function test_update_does_not_change_cost_price_when_field_is_absent(): void
    {
        Event::fake();

        $updatedProduct = Product::factory()->make([
            'id' => 1,
            'cost_price' => 99.90,
            'active' => false,
            'quantity' => 10,
            'min_quantity' => 5,
        ]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(fn (Product $product, array $data) => $product->id === 1
                && ! array_key_exists('cost_price', $data)
                && $data['active'] === false)
            ->andReturn($updatedProduct);

        $original = Product::factory()->make(['id' => 1, 'cost_price' => 99.90]);
        $dto = new ProductDTO(active: false);

        $this->makeService($repo)->update($original, $dto);
    }

    public function test_create_generates_new_slug_when_soft_deleted_product_already_uses_base_slug(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1, 'quantity' => 10, 'min_quantity' => 5]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('slugExists')->with('meu-produto', null)->once()->andReturn(true);
        $repo->shouldReceive('slugExists')->with('meu-produto-1', null)->once()->andReturn(false);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['slug'] === 'meu-produto-1')
            ->andReturn($product);
        $repo->shouldReceive('syncTags')->andReturn(null);
        $repo->shouldReceive('findById')->andReturn($product);

        $dto = new ProductDTO(name: 'Meu Produto', price: 10.0, quantity: 10, categoryId: 1);

        $this->makeService($repo)->create($dto);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_delete_calls_repository_delete(): void
    {
        Event::fake();

        $product = Product::factory()->make(['id' => 1]);

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('delete')->once()->with($product)->andReturn(true);

        $result = $this->makeService($repo)->delete($product);

        $this->assertTrue($result);
    }

    // ── LowStock ─────────────────────────────────────────────────────────────

    public function test_low_stock_delegates_to_repository(): void
    {
        $collection = new Collection();

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('lowStock')->once()->andReturn($collection);

        $result = $this->makeService($repo)->lowStock();

        $this->assertSame($collection, $result);
    }
}
