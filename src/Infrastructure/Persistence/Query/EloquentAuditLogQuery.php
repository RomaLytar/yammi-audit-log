<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Query;

use DateTimeImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Yammi\AuditLog\Application\Contract\AuditLogQuery;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;
use Yammi\AuditLog\Domain\Audit\Query\PagedRecords;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Infrastructure\Persistence\Mapper\AuditRecordMapper;

/** @internal */
final class EloquentAuditLogQuery implements AuditLogQuery
{
    public function __construct(
        private readonly AuditRecordMapper $mapper,
    ) {}

    public function paginate(AuditCriteria $criteria, int $page = 1, int $perPage = 25): PagedRecords
    {
        $query = AuditRecordModel::query()
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        $this->applyCriteria($query, $criteria);

        $total = (clone $query)->count();

        $models = $query->forPage($page, $perPage)->get();

        $records = [];

        foreach ($models as $model) {
            $records[] = $this->mapper->toDomain($model);
        }

        return new PagedRecords($records, $total, $page, $perPage);
    }

    public function all(AuditCriteria $criteria, int $limit): array
    {
        $query = AuditRecordModel::query()
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($limit);

        $this->applyCriteria($query, $criteria);

        $records = [];

        foreach ($query->get() as $model) {
            $records[] = $this->mapper->toDomain($model);
        }

        return $records;
    }

    public function chain(string $correlationId): array
    {
        $models = AuditRecordModel::query()
            ->where('correlation_id', $correlationId)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $records = [];

        foreach ($models as $model) {
            $records[] = $this->mapper->toDomain($model);
        }

        return $records;
    }

    public function distinctModels(): array
    {
        return $this->distinctColumn('auditable_type');
    }

    public function distinctActorTypes(): array
    {
        return $this->distinctColumn('actor_type');
    }

    public function countNoise(): int
    {
        return AuditRecordModel::query()->where('is_noise', true)->count();
    }

    public function countAll(): int
    {
        return AuditRecordModel::query()->count();
    }

    public function countSince(DateTimeImmutable $cutoff): int
    {
        return AuditRecordModel::query()
            ->where('occurred_at', '>=', $cutoff->format('Y-m-d H:i:s'))
            ->count();
    }

    public function chainSizes(array $correlationIds): array
    {
        if ($correlationIds === []) {
            return [];
        }

        $counts = [];

        $rows = AuditRecordModel::query()
            ->whereIn('correlation_id', $correlationIds)
            ->groupBy('correlation_id')
            ->selectRaw('correlation_id, count(*) as total')
            ->get();

        foreach ($rows as $row) {
            $id = $row->getAttribute('correlation_id');

            if (is_string($id)) {
                $counts[$id] = (int) $row->getAttribute('total');
            }
        }

        return $counts;
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
            $query->whereRaw("actor_label like ? escape '!'", ['%'.$this->escapeLike($criteria->actorLabel).'%']);
        }

        if ($criteria->onlyNoise !== null) {
            $query->where('is_noise', $criteria->onlyNoise);
        }

        if ($criteria->search !== null) {
            $term = '%'.$this->escapeLike($criteria->search).'%';
            $changesAsText = $this->changesAsText($query);

            $query->where(function (Builder $nested) use ($term, $changesAsText, $criteria): void {
                $nested->whereRaw("{$changesAsText} like ? escape '!'", [$term])
                    ->orWhere('auditable_id', $criteria->search);
            });
        }

        if ($criteria->from !== null) {
            $query->where('occurred_at', '>=', $criteria->from->setTime(0, 0)->format('Y-m-d H:i:s'));
        }

        if ($criteria->to !== null) {
            $query->where('occurred_at', '<', $criteria->to->setTime(0, 0)->modify('+1 day')->format('Y-m-d H:i:s'));
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    /**
     * The changes column is JSON; LIKE needs a text expression per driver.
     *
     * @param  Builder<AuditRecordModel>  $query
     */
    private function changesAsText(Builder $query): string
    {
        $connection = $query->getConnection();
        $driver = $connection instanceof Connection ? $connection->getDriverName() : '';

        return match ($driver) {
            'pgsql' => 'changes::text',
            'mysql', 'mariadb' => 'cast(changes as char)',
            default => 'cast(changes as text)',
        };
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
}
