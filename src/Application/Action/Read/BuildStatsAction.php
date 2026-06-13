<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action\Read;

use DateInterval;
use DateTimeImmutable;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\Contract\Query\AuditLogQuery;
use Yammi\AuditLog\Application\Contract\Query\AuditStatsQuery;
use Yammi\AuditLog\Application\DTO\Audit\AuditFilterData;
use Yammi\AuditLog\Application\DTO\Stats\StatsData;
use Yammi\AuditLog\Application\Service\CriteriaFactory;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;

/** @internal */
final class BuildStatsAction
{
    private const WINDOW_DAYS = 30;

    private const ACTIVITY_DAYS = 30;

    private const MODEL_LIMIT = 10;

    private const CASCADE_LIMIT = 8;

    public function __construct(
        private readonly AuditStatsQuery $stats,
        private readonly AuditLogQuery $query,
        private readonly CriteriaFactory $criteria,
        private readonly Clock $clock,
    ) {}

    /**
     * Projected rows = the steady-state table size if the recent (filtered)
     * write rate holds for the whole retention window; null when retention
     * is disabled.
     */
    public function __invoke(AuditFilterData $filters, int $retentionDays): StatsData
    {
        $criteria = $this->criteria->fromFilters($filters);
        $cutoff = $this->clock->now()->sub(new DateInterval('P'.self::WINDOW_DAYS.'D'));

        $last30Days = $this->stats->count($this->windowed($criteria, $cutoff));
        $perDay = round($last30Days / self::WINDOW_DAYS, 1);

        return new StatsData(
            total: $this->stats->count($criteria),
            last30Days: $last30Days,
            perDay: $perDay,
            projectedRows: $retentionDays > 0 ? (int) round($perDay * $retentionDays) : null,
            byEvent: $this->stats->eventBreakdown($criteria),
            byActorType: $this->stats->actorTypeBreakdown($criteria),
            byModel: $this->stats->modelBreakdown($criteria, self::MODEL_LIMIT),
            byDay: $this->stats->dailyCounts(
                $criteria,
                $this->clock->now()->sub(new DateInterval('P'.(self::ACTIVITY_DAYS - 1).'D')),
                self::ACTIVITY_DAYS,
            ),
            filters: $filters,
            models: $this->query->distinctModels(),
            actorTypes: $this->query->distinctActorTypes(),
            events: ChangeType::values(),
            topCascades: $this->stats->topCascades($criteria, self::CASCADE_LIMIT),
        );
    }

    private function windowed(AuditCriteria $criteria, DateTimeImmutable $cutoff): AuditCriteria
    {
        $from = $criteria->from !== null && $criteria->from > $cutoff ? $criteria->from : $cutoff;

        return new AuditCriteria(
            auditableType: $criteria->auditableType,
            event: $criteria->event,
            actorType: $criteria->actorType,
            actorLabel: $criteria->actorLabel,
            from: $from,
            to: $criteria->to,
            onlyNoise: $criteria->onlyNoise,
            search: $criteria->search,
        );
    }
}
