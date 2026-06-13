<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO\Stats;

use Yammi\AuditLog\Application\DTO\Audit\AuditFilterData;

final class StatsData
{
    /**
     * @param  array<string, int>  $byEvent
     * @param  array<string, int>  $byActorType
     * @param  array<string, int>  $byModel
     * @param  array<string, int>  $byField
     * @param  array<string, int>  $byDay
     * @param  list<string>  $models
     * @param  list<string>  $actorTypes
     * @param  list<string>  $events
     * @param  list<array{correlation_id: string, writes: int, models: int, depth: int}>  $topCascades
     */
    public function __construct(
        public readonly int $total,
        public readonly int $last30Days,
        public readonly float $perDay,
        public readonly ?int $projectedRows,
        public readonly array $byEvent,
        public readonly array $byActorType,
        public readonly array $byModel,
        public readonly array $byDay,
        public readonly AuditFilterData $filters,
        public readonly array $models = [],
        public readonly array $actorTypes = [],
        public readonly array $events = [],
        public readonly array $topCascades = [],
        public readonly array $byField = [],
    ) {}
}
