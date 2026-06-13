<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Repository;

use Yammi\AuditLog\Infrastructure\Integrity\IntegrityHasher;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditChainStateModel;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

/**
 * The single insert path for audit rows (synchronous and queued). With
 * integrity enabled the insert runs in a transaction that locks the single
 * chain-state row first; because that row always exists, even concurrent
 * writers inserting the very first record cannot fork the hash chain.
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
            $state = AuditChainStateModel::query()->lockForUpdate()->findOrFail(AuditChainStateModel::ROW_ID);

            $previous = $state->getAttribute('last_hash');

            $hash = $this->hasher->hash(is_string($previous) ? $previous : null, $row);

            $row['integrity_hash'] = $hash;

            AuditRecordModel::query()->create($row);

            $state->setAttribute('last_hash', $hash);
            $state->save();
        });
    }
}
