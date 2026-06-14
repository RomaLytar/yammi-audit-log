<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Eloquent;

/**
 * Builds the changed-keys index rows for one audit record from its changes
 * payload — the single place the writer and the backfill command share so both
 * derive the same field names.
 *
 * @internal
 */
final class ChangedKeyRows
{
    /**
     * @return list<array{audit_id: int, key: string}>
     */
    public static function build(int $auditId, mixed $changes): array
    {
        if (! is_array($changes)) {
            return [];
        }

        $rows = [];

        foreach (array_keys($changes) as $key) {
            $name = mb_substr((string) $key, 0, 64);

            if ($name === '') {
                continue;
            }

            $rows[] = ['audit_id' => $auditId, 'key' => $name];
        }

        return $rows;
    }
}
