<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Repository;

use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Infrastructure\Persistence\Mapper\AuditRecordMapper;

final class EloquentAuditRecordRepository implements AuditRecordRepository
{
    public function __construct(
        private readonly AuditRecordMapper $mapper,
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
}
