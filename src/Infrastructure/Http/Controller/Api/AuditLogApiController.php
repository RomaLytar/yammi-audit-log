<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yammi\AuditLog\Infrastructure\AuditLogManager;

/**
 * JSON endpoints mirroring the facade, for SPA admins that cannot call PHP.
 * Off by default; the host chooses the middleware (e.g. auth:sanctum).
 *
 * @internal
 */
final class AuditLogApiController
{
    public function __construct(
        private readonly AuditLogManager $manager,
    ) {}

    public function changes(Request $request): JsonResponse
    {
        return new JsonResponse(['data' => $this->manager->changes($request->query())]);
    }

    public function noise(Request $request): JsonResponse
    {
        return new JsonResponse(['data' => $this->manager->noise($request->query())]);
    }

    public function chain(string $correlation): JsonResponse
    {
        $chain = $this->manager->chain($correlation);

        if ($chain === null) {
            return new JsonResponse(['error' => 'Unknown correlation id.'], 404);
        }

        return new JsonResponse(['data' => $chain]);
    }

    public function stats(Request $request): JsonResponse
    {
        return new JsonResponse(['data' => $this->manager->stats($request->query())]);
    }

    public function timeline(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'auditable_type' => 'required|string',
            'auditable_id' => 'required|string',
            'limit' => 'sometimes|integer|min:1|max:500',
        ]);

        $limit = $validated['limit'] ?? 50;

        return new JsonResponse(['data' => $this->manager->for(
            is_string($validated['auditable_type']) ? $validated['auditable_type'] : '',
            is_string($validated['auditable_id']) ? $validated['auditable_id'] : '',
            is_numeric($limit) ? (int) $limit : 50,
        )]);
    }
}
