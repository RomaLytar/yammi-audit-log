<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;
use Yammi\AuditLog\Domain\Audit\Query\PagedRecords;
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

        return $this->toDomainList($models->all());
    }

    public function paginate(AuditCriteria $criteria, int $page = 1, int $perPage = 25): PagedRecords
    {
        $query = AuditRecordModel::query()
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        $this->applyCriteria($query, $criteria);

        $total = (clone $query)->count();

        $models = $query->forPage($page, $perPage)->get();

        return new PagedRecords($this->toDomainList($models->all()), $total, $page, $perPage);
    }

    public function findByCorrelation(string $correlationId): array
    {
        $models = AuditRecordModel::query()
            ->where('correlation_id', $correlationId)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        return $this->toDomainList($models->all());
    }

    public function distinctModels(): array
    {
        return $this->distinctColumn('auditable_type');
    }

    public function distinctActorTypes(): array
    {
        return $this->distinctColumn('actor_type');
    }

    /**
     * @param  Builder<AuditRecordModel>  $query
     */
    private function applyCriteria(Builder $query, AuditCriteria $criteria): void
    {
        $query->where(array_filter([
            'auditable_type' => $criteria->auditableType,
            'event' => $criteria->event?->value,
            'actor_type' => $criteria->actorType?->value,
        ], static fn (?string $value): bool => $value !== null));

        if ($criteria->actorLabel !== null) {
            $query->where('actor_label', 'like', '%'.$criteria->actorLabel.'%');
        }

        foreach (array_filter(['>=' => $criteria->from, '<=' => $criteria->to]) as $operator => $date) {
            $query->whereDate('occurred_at', $operator, $date->format('Y-m-d'));
        }
    }

    /**
     * @return list<string>
     */
    private function distinctColumn(string $column): array
    {
        $values = [];

        foreach (AuditRecordModel::query()->distinct()->orderBy($column)->pluck($column) as $value) {
            if (is_string($value) && $value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * @param  array<int, AuditRecordModel>  $models
     * @return list<AuditRecord>
     */
    private function toDomainList(array $models): array
    {
        $records = [];

        foreach ($models as $model) {
            $records[] = $this->mapper->toDomain($model);
        }

        return $records;
    }
}
