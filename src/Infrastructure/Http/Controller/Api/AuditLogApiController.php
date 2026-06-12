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

    public function state(Request $request): JsonResponse
    {
        [$type, $id] = $this->auditable($request, ['at' => 'sometimes|nullable|date']);

        $at = $request->query('at');

        return new JsonResponse(['data' => $this->manager->stateAt(
            $type,
            $id,
            is_string($at) && trim($at) !== '' ? trim($at) : null,
        )]);
    }

    public function recordView(Request $request): JsonResponse
    {
        [$type, $id] = $this->auditable($request);

        return new JsonResponse(['data' => $this->manager->recordView($type, $id)]);
    }

    public function subjectReport(Request $request): JsonResponse
    {
        [$type, $id] = $this->auditable($request);

        return new JsonResponse(['data' => $this->manager->subjectReport($type, $id)]);
    }

    public function anomalies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'window' => 'sometimes|nullable|integer|min:1|max:43200',
        ]);

        $window = $validated['window'] ?? null;

        return new JsonResponse(['data' => $this->manager->anomalies(
            is_numeric($window) ? (int) $window : null,
        )]);
    }

    /**
     * @param  array<string, string>  $extraRules
     * @return array{0: string, 1: string}
     */
    private function auditable(Request $request, array $extraRules = []): array
    {
        $validated = $request->validate($extraRules + [
            'auditable_type' => 'required|string|max:191',
            'auditable_id' => 'required|string|max:64',
        ]);

        return [
            is_string($validated['auditable_type']) ? $validated['auditable_type'] : '',
            is_string($validated['auditable_id']) ? $validated['auditable_id'] : '',
        ];
    }
}
