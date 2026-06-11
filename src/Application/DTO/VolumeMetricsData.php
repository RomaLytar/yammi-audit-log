<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO;

/** @internal */
final class VolumeMetricsData
{
    public function __construct(
        public readonly int $total,
        public readonly int $last30Days,
        public readonly float $perDay,
        public readonly ?int $projectedRows,
    ) {}
}
