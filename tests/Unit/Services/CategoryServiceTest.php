<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(CategoryRepositoryInterface $repo): CategoryService
    {
        return new CategoryService($repo);
    }

    public function test_create_auto_generates_unique_slug(): void
    {
        $category = Category::factory()->make(['id' => 1]);

        $repo = Mockery::mock(CategoryRepositoryInterface::class);
        $repo->shouldReceive('slugExists')->with('nova-categoria', null)->once()->andReturn(true);
        $repo->shouldReceive('slugExists')->with('nova-categoria-1', null)->once()->andReturn(false);
        $repo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['slug'] === 'nova-categoria-1')
            ->andReturn($category);

        $this->makeService($repo)->create(['name' => 'Nova Categoria']);
    }

    public function test_update_auto_generates_unique_slug_when_name_changes_without_explicit_slug(): void
    {
        $updatedCategory = Category::factory()->make(['id' => 1]);

        $repo = Mockery::mock(CategoryRepositoryInterface::class);
        $repo->shouldReceive('slugExists')->with('novo-nome', 1)->once()->andReturn(true);
        $repo->shouldReceive('slugExists')->with('novo-nome-1', 1)->once()->andReturn(false);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(fn (Category $category, array $data) => $category->id === 1
                && $data['name'] === 'Novo Nome'
                && $data['slug'] === 'novo-nome-1')
            ->andReturn($updatedCategory);

        $original = Category::factory()->make(['id' => 1, 'name' => 'Nome Antigo']);

        $this->makeService($repo)->update($original, ['name' => 'Novo Nome']);
    }

    public function test_update_uses_explicit_slug_as_unique_base(): void
    {
        $updatedCategory = Category::factory()->make(['id' => 1]);

        $repo = Mockery::mock(CategoryRepositoryInterface::class);
        $repo->shouldReceive('slugExists')->with('slug-manual', 1)->once()->andReturn(false);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(fn (Category $category, array $data) => $category->id === 1
                && $data['name'] === 'Novo Nome'
                && $data['slug'] === 'slug-manual')
            ->andReturn($updatedCategory);

        $original = Category::factory()->make(['id' => 1, 'name' => 'Nome Antigo']);

        $this->makeService($repo)->update($original, [
            'name' => 'Novo Nome',
            'slug' => 'Slug Manual',
        ]);
    }

    public function test_update_preserves_existing_slug_when_same_slug_is_explicitly_provided(): void
    {
        $updatedCategory = Category::factory()->make([
            'id' => 1,
            'name' => 'Novo Nome',
            'slug' => 'slug-estavel',
        ]);

        $repo = Mockery::mock(CategoryRepositoryInterface::class);
        $repo->shouldReceive('slugExists')->with('slug-estavel', 1)->once()->andReturn(false);
        $repo->shouldReceive('update')
            ->once()
            ->withArgs(fn (Category $category, array $data) => $category->id === 1
                && $data['name'] === 'Novo Nome'
                && $data['slug'] === 'slug-estavel')
            ->andReturn($updatedCategory);

        $original = Category::factory()->make([
            'id' => 1,
            'name' => 'Nome Antigo',
            'slug' => 'slug-estavel',
        ]);

        $this->makeService($repo)->update($original, [
            'name' => 'Novo Nome',
            'slug' => 'slug-estavel',
        ]);
    }
}
