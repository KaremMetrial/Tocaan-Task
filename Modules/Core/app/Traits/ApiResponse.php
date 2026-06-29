<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Standardizes the JSON envelope for all API responses.
 *
 * Success: { "success": true,  "message": ..., "data": ... , (meta) }
 * Error:   { "success": false, "message": ..., "errors": ... }
 */
trait ApiResponse
{
    /**
     * Return a standardized success response.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Request completed successfully.',
        int $status = Response::HTTP_OK
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        // Unwrap API resources / paginators and surface pagination meta when present.
        if ($data instanceof ResourceCollection || $data instanceof JsonResource) {
            $response = $data->response();
            $original = $response->getData(true);

            $payload['data'] = $original['data'] ?? $original;

            foreach (['links', 'meta'] as $key) {
                if (isset($original[$key])) {
                    $payload[$key] = $original[$key];
                }
            }
        } elseif ($data instanceof AbstractPaginator) {
            $paginated = $data->toArray();

            $payload['data'] = $paginated['data'];
            $payload['meta'] = $this->paginationMeta($data);
        } else {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    /**
     * Return a standardized "created" response (201).
     */
    protected function createdResponse(
        mixed $data = null,
        string $message = 'Resource created successfully.'
    ): JsonResponse {
        return $this->successResponse($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Return a standardized empty response (204).
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Return a standardized error response.
     *
     * @param  array<string, mixed>|null  $errors
     */
    protected function errorResponse(
        string $message = 'Something went wrong.',
        int $status = Response::HTTP_BAD_REQUEST,
        ?array $errors = null
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Build a compact pagination meta block.
     *
     * @param  AbstractPaginator<int, mixed>  $paginator
     * @return array<string, mixed>
     */
    private function paginationMeta(AbstractPaginator $paginator): array
    {
        $data = $paginator->toArray();

        return [
            'current_page' => $data['current_page'] ?? null,
            'per_page' => $data['per_page'] ?? null,
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
            'total' => $data['total'] ?? null,
            'last_page' => $data['last_page'] ?? null,
        ];
    }
}
