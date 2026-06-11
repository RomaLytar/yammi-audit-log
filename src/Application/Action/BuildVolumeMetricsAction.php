<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action;

use DateInterval;
use Yammi\AuditLog\Application\Contract\AuditLogQuery;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\DTO\VolumeMetricsData;

/** @internal */
final class BuildVolumeMetricsAction
{
    private const WINDOW_DAYS = 30;

    public function __construct(
        private readonly AuditLogQuery $query,
        private readonly Clock $clock,
    ) {}

    /**
     * Projected rows = the steady-state table size if the recent write rate
     * holds for the whole retention window; null when retention is disabled.
     */
    public function __invoke(int $retentionDays): VolumeMetricsData
    {
        $total = $this->query->countAll();
        $last30Days = $this->query->countSince(
            $this->clock->now()->sub(new DateInterval('P'.self::WINDOW_DAYS.'D')),
        );

        $perDay = round($last30Days / self::WINDOW_DAYS, 1);

        return new VolumeMetricsData(
            total: $total,
            last30Days: $last30Days,
            perDay: $perDay,
            projectedRows: $retentionDays > 0 ? (int) round($perDay * $retentionDays) : null,
        );
    }
}
