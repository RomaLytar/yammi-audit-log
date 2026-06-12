<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Integrity;

/**
 * Chains audit rows: every hash covers the previous hash plus the immutable
 * fields of the row, so editing or deleting a stored record breaks every
 * hash after it and audit-log:verify can name the first tampered row.
 *
 * @internal
 */
final class IntegrityHasher
{
    private const FIELDS = [
        'auditable_type',
        'auditable_id',
        'event',
        'changes',
        'actor_type',
        'actor_id',
        'actor_label',
        'origin_type',
        'origin_id',
        'origin_label',
        'correlation_id',
        'occurred_at',
    ];

    /**
     * @param  array<string, mixed>  $row
     */
    public function hash(?string $previousHash, array $row): string
    {
        $canonical = [];

        foreach (self::FIELDS as $field) {
            $value = $row[$field] ?? null;
            $canonical[$field] = is_array($value) ? json_encode($value) : (is_scalar($value) ? (string) $value : null);
        }

        return hash('sha256', ($previousHash ?? '').json_encode($canonical));
    }
}
