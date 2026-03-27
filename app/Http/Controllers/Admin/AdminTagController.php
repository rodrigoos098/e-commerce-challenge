<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tags,name'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:tags,slug'],
        ]);

        $this->tagService->create($validated);

        return redirect('/admin/tags')->with('success', 'Tag criada com sucesso!');
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:tags,name,'.$tag->id],
            'slug' => ['nullable', 'string', 'max:255', 'unique:tags,slug,'.$tag->id],
        ]);

        $this->tagService->update($tag, $validated);

        return redirect('/admin/tags')->with('success', 'Tag atualizada com sucesso!');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $this->tagService->delete($tag);

        return redirect('/admin/tags')->with('success', 'Tag excluida com sucesso!');
    }
}
