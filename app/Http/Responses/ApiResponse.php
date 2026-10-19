<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

final class ApiResponse
{
    public static function success(mixed $data, string $message, int $status = 200, array $meta = []): JsonResponse
    {
        if ($data instanceof JsonResource) {
            return self::fromResource($data, $message, $status, $meta);
        }

        $body = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        if ($meta !== []) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $status);
    }

    public static function error(string $message, int $status, array $extra = []): JsonResponse
    {
        return response()->json([
            ...$extra,
            'success' => false,
            'message' => $message,
        ], $status);
    }

    private static function fromResource(JsonResource $resource, string $message, int $status, array $meta): JsonResponse
    {
        $envelope = ['success' => true, 'message' => $message];

        $mergedMeta = array_merge($resource->additional['meta'] ?? [], $meta);

        if ($mergedMeta !== []) {
            $envelope['meta'] = $mergedMeta;
        }

        return $resource
            ->additional(array_merge($resource->additional, $envelope))
            ->response()
            ->setStatusCode($status);
    }
}
