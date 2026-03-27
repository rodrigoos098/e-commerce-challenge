<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponseTrait
{
    /**
     * Return a successful JSON response.
     */
    protected function successResponse(mixed $data, int $statusCode = 200): JsonResponse
    {
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return $data->additional(['success' => true])->response()->setStatusCode($statusCode);
        }

        return response()->json(['success' => true, 'data' => $data], $statusCode);
    }

    /**
     * Return a paginated JSON response.
     */
    protected function paginatedResponse(ResourceCollection $collection): JsonResponse
    {
        return $collection->additional(['success' => true])->response();
    }

    /**
     * Return an error JSON response.
     *
     * @param  array<string, mixed>|null  $errors
     */
    protected function errorResponse(string $message, int $statusCode = 400, ?array $errors = null): JsonResponse
    {
        $payload = ['success' => false, 'message' => $message];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * Return a not-found JSON response.
     */
    protected function notFoundResponse(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Return a created resource JSON response.
     */
    protected function createdResponse(mixed $data): JsonResponse
    {
        return $this->successResponse($data, 201);
    }
}
