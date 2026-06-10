<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Repository;

use DateTimeImmutable;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Infrastructure\Persistence\Mapper\AuditRecordMapper;

/** @internal */
final class EloquentAuditRecordRepository implements AuditRecordRepository
{
    private const PRUNE_CHUNK = 1000;

    public function __construct(
        private readonly AuditRecordMapper $mapper,
        private readonly int $pruneChunkSize = self::PRUNE_CHUNK,
    ) {}

    public function save(AuditRecord $record): void
    {
        AuditRecordModel::query()->create($this->mapper->toRow($record)->toArray());
    }

    public function timelineFor(AuditableReference $auditable, int $limit = 50): array
    {
        $models = AuditRecordModel::query()
            ->where('auditable_type', $auditable->type)
            ->where('auditable_id', $auditable->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $records = [];

        foreach ($models as $model) {
            $records[] = $this->mapper->toDomain($model);
        }

        return $records;
    }

    public function deleteOlderThan(DateTimeImmutable $cutoff): int
    {
        $total = 0;

        do {
            $ids = AuditRecordModel::query()
                ->where('occurred_at', '<', $cutoff->format('Y-m-d H:i:s'))
                ->orderBy('id')
                ->limit($this->pruneChunkSize)
                ->pluck('id')
                ->all();

            if ($ids === []) {
                return $total;
            }

            $total += AuditRecordModel::query()->whereIn('id', $ids)->delete();
        } while (count($ids) === $this->pruneChunkSize);

        return $total;
    }
}
