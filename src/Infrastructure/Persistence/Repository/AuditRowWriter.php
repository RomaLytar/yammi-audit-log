<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Repository;

use Yammi\AuditLog\Infrastructure\Integrity\IntegrityHasher;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

/**
 * The single insert path for audit rows (synchronous and queued). With
 * integrity enabled the insert runs in a transaction that locks the chain
 * head, so concurrent writers cannot fork the hash chain.
 *
 * @internal
 */
final class AuditRowWriter
{
    public function __construct(
        private readonly IntegrityHasher $hasher,
        private readonly bool $integrityEnabled = false,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public function insert(array $row): void
    {
        if (! $this->integrityEnabled) {
            AuditRecordModel::query()->create($row);

            return;
        }

        $model = new AuditRecordModel;

        $model->getConnection()->transaction(function () use ($row): void {
            $previous = AuditRecordModel::query()
                ->withoutGlobalScopes()
                ->orderByDesc('id')
                ->lockForUpdate()
                ->value('integrity_hash');

            $row['integrity_hash'] = $this->hasher->hash(
                is_string($previous) ? $previous : null,
                $row,
            );

            AuditRecordModel::query()->create($row);
        });
    }
}
