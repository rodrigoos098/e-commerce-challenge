<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\StoreTagRequest;
use App\Http\Requests\Web\Admin\UpdateTagRequest;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminTagController extends Controller
{
    public function __construct(
        private readonly TagService $tagService,
    ) {
    }

    public function index(): Response
    {
        $tags = $this->tagService->all()->loadCount('products');

        return Inertia::render('Admin/Tags/Index', [
            'tags' => $tags->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'products_count' => $tag->products_count,
            ])->toArray(),
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->tagService->create($validated);

        return redirect('/admin/tags')->with('success', 'Tag criada com sucesso!');
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validated();

        $this->tagService->update($tag, $validated);

        return redirect('/admin/tags')->with('success', 'Tag atualizada com sucesso!');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $this->tagService->delete($tag);

        return redirect('/admin/tags')->with('success', 'Tag excluida com sucesso!');
    }
}
