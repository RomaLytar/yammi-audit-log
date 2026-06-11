<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Bus\Dispatcher;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Infrastructure\Job\PersistAuditRecordJob;
use Yammi\AuditLog\Infrastructure\Persistence\Mapper\AuditRecordMapper;

/**
 * Defers the audit insert to the queue. Everything that depends on the moment
 * of change — actor, origin, correlation, redacted diff — is already inside
 * the record; the worker only writes the row.
 *
 * @internal
 */
final class QueuedAuditRecordRepository implements AuditRecordRepository
{
    public function __construct(
        private readonly EloquentAuditRecordRepository $inner,
        private readonly AuditRecordMapper $mapper,
        private readonly Dispatcher $bus,
        private readonly ?string $queue = null,
    ) {}

    public function save(AuditRecord $record): void
    {
        $job = new PersistAuditRecordJob($this->mapper->toRow($record)->toArray());

        if ($this->queue !== null && $this->queue !== '') {
            $job->onQueue($this->queue);
        }

        $this->bus->dispatch($job);
    }

    public function timelineFor(AuditableReference $auditable, int $limit = 50): array
    {
        return $this->inner->timelineFor($auditable, $limit);
    }

    public function deleteOlderThan(DateTimeImmutable $cutoff): int
    {
        return $this->inner->deleteOlderThan($cutoff);
    }
}
