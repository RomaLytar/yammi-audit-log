<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Retention;

use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\DTO\Audit\LegalHoldData;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditLegalHoldModel;

/**
 * Places and lifts legal holds on a subject's audit trail. While a subject is
 * held, retention skips every one of its records (past and future), so
 * litigation data is never pruned away. Releasing the hold lets retention
 * resume on the next run.
 *
 * @internal
 */
final class LegalHoldRegistry
{
    public function __construct(
        private readonly Clock $clock,
    ) {}

    public function place(string $auditableType, string $auditableId, ?string $reason = null): void
    {
        AuditLegalHoldModel::query()->updateOrCreate(
            ['auditable_type' => $auditableType, 'auditable_id' => $auditableId],
            [
                'reason' => $reason !== null && $reason !== '' ? mb_substr($reason, 0, 1000) : null,
                'placed_at' => $this->clock->now()->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function release(string $auditableType, string $auditableId): bool
    {
        return AuditLegalHoldModel::query()
            ->where('auditable_type', $auditableType)
            ->where('auditable_id', $auditableId)
            ->delete() > 0;
    }

    public function isHeld(string $auditableType, string $auditableId): bool
    {
        return AuditLegalHoldModel::query()
            ->where('auditable_type', $auditableType)
            ->where('auditable_id', $auditableId)
            ->exists();
    }

    /**
     * @return list<LegalHoldData>
     */
    public function all(): array
    {
        $holds = [];

        foreach (AuditLegalHoldModel::query()->orderByDesc('id')->get() as $row) {
            $holds[] = new LegalHoldData(
                auditableType: (string) $row->getAttribute('auditable_type'),
                auditableId: (string) $row->getAttribute('auditable_id'),
                reason: $this->nullableString($row->getAttribute('reason')),
                placedAt: $this->nullableString($row->getAttribute('placed_at')),
            );
        }

        return $holds;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
