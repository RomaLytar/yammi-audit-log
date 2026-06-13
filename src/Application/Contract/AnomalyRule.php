<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract;

use Yammi\AuditLog\Application\DTO\AnomalyData;
use Yammi\AuditLog\Application\DTO\AnomalyWindow;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;

/**
 * A host-defined anomaly rule: detection as code. Rules are plain classes —
 * version them in git, unit-test them by passing entries directly — and run
 * alongside the built-in rate/mass-delete/off-hours checks. Each rule sees the
 * window's recorded changes and returns its findings, computing severity as it
 * sees fit.
 */
interface AnomalyRule
{
    public function key(): string;

    /**
     * @param  list<TimelineEntryData>  $entries
     * @return list<AnomalyData>
     */
    public function evaluate(array $entries, AnomalyWindow $window): array;
}
