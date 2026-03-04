<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryService $categoryService,
    ) {
    }

    public function index(Request $request): Response
    {
        $featuredProducts = $this->productService->paginate(['active' => true, 'in_stock' => true], 8);
        $categories = $this->categoryService->tree();

        return Inertia::render('Home', [
            'featured_products' => ProductResource::collection($featuredProducts->items())->toArray($request),
            'categories' => CategoryResource::collection($categories)->toArray($request),
        ]);
    }
}
