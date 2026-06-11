<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Query;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Yammi\AuditLog\Application\Contract\AuditStatsQuery;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

/** @internal */
final class EloquentAuditStatsQuery implements AuditStatsQuery
{
    public function __construct(
        private readonly AuditCriteriaApplier $applier,
    ) {}

    public function count(AuditCriteria $criteria): int
    {
        return $this->query($criteria)->count();
    }

    public function eventBreakdown(AuditCriteria $criteria): array
    {
        return $this->breakdown('event', $criteria);
    }

    public function actorTypeBreakdown(AuditCriteria $criteria): array
    {
        return $this->breakdown('actor_type', $criteria);
    }

    public function modelBreakdown(AuditCriteria $criteria, int $limit = 10): array
    {
        return $this->breakdown('auditable_type', $criteria, $limit);
    }

    public function dailyCounts(AuditCriteria $criteria, DateTimeImmutable $from, int $days): array
    {
        $start = $from->setTime(0, 0);

        $rows = $this->query($criteria)
            ->where('occurred_at', '>=', $start->format('Y-m-d H:i:s'))
            ->selectRaw($this->dayExpression().' as day, count(*) as total')
            ->groupBy('day')
            ->get();

        $found = [];

        foreach ($rows as $row) {
            $day = $row->getAttribute('day');

            if (is_string($day)) {
                $found[$day] = (int) $row->getAttribute('total');
            }
        }

        $out = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $start->add(new DateInterval('P'.$i.'D'))->format('Y-m-d');
            $out[$day] = $found[$day] ?? 0;
        }

        return $out;
    }

    /**
     * @return Builder<AuditRecordModel>
     */
    private function query(AuditCriteria $criteria): Builder
    {
        $query = AuditRecordModel::query();

        $this->applier->apply($query, $criteria);

        return $query;
    }

    /**
     * @return array<string, int>
     */
    private function breakdown(string $column, AuditCriteria $criteria, ?int $limit = null): array
    {
        $query = $this->query($criteria)
            ->selectRaw("{$column}, count(*) as total")
            ->groupBy($column)
            ->orderByDesc('total');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $out = [];

        foreach ($query->get() as $row) {
            $key = $row->getAttribute($column);

            if (is_string($key) && $key !== '') {
                $out[$key] = (int) $row->getAttribute('total');
            }
        }

        return $out;
    }

    private function dayExpression(): string
    {
        $connection = AuditRecordModel::query()->getConnection();
        $driver = $connection instanceof Connection ? $connection->getDriverName() : '';

        return match ($driver) {
            'pgsql' => "to_char(occurred_at, 'YYYY-MM-DD')",
            'mysql', 'mariadb' => "date_format(occurred_at, '%Y-%m-%d')",
            default => "strftime('%Y-%m-%d', occurred_at)",
        };
    }
}
