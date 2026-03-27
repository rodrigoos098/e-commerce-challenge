<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTagRequest;
use App\Http\Requests\Api\V1\UpdateTagRequest;
use App\Http\Resources\Api\V1\TagResource;
use App\Models\Tag;
use App\Services\TagService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly TagService $tagService,
    ) {
    }

    public function index(): JsonResponse
    {
        $tags = $this->tagService->all()->loadCount('products');

        return $this->successResponse(TagResource::collection($tags));
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = $this->tagService->create($request->validated());

        return $this->createdResponse(new TagResource($tag));
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $updated = $this->tagService->update($tag, $request->validated());

        return $this->successResponse(new TagResource($updated));
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $this->tagService->delete($tag);

        return $this->successResponse(null);
    }
}
