<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\StoreCategoryRequest;
use App\Http\Requests\Web\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminCategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {
    }

    public function index(): Response
    {
        $categories = $this->categoryService->all();

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'description' => $cat->description,
                'parent_id' => $cat->parent_id,
                'active' => (bool) $cat->active,
            ])->toArray(),
        ]);
    }

    public function create(): Response
    {
        $categories = $this->categoryService->all();

        return Inertia::render('Admin/Categories/Create', [
            'categories' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'parent_id' => $cat->parent_id,
                'active' => (bool) $cat->active,
            ])->toArray(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->categoryService->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'active' => $validated['active'] ?? true,
        ]);

        return redirect('/admin/categories')->with('success', 'Categoria criada com sucesso!');
    }

    public function edit(Category $category): Response
    {
        $allCategories = $this->categoryService->all();

        return Inertia::render('Admin/Categories/Edit', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'active' => (bool) $category->active,
            ],
            'categories' => $allCategories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'parent_id' => $cat->parent_id,
                'active' => (bool) $cat->active,
            ])->toArray(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();

        $this->categoryService->update($category, [
            'name' => $validated['name'] ?? $category->name,
            'description' => array_key_exists('description', $validated) ? $validated['description'] : $category->description,
            'parent_id' => array_key_exists('parent_id', $validated) ? $validated['parent_id'] : $category->parent_id,
            'active' => $validated['active'] ?? $category->active,
        ]);

        return redirect('/admin/categories')->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categoryService->delete($category);

        return redirect('/admin/categories')->with('success', 'Categoria excluída com sucesso!');
    }
}
