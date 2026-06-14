<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO\Anomaly;

final class AnomalyData
{
    public const RULE_RATE_SPIKE = 'rate_spike';

    public const RULE_MASS_DELETE = 'mass_delete';

    public const RULE_OFF_HOURS = 'off_hours';

    public const RULE_CASCADE = 'cascade_weight';

    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    public function __construct(
        public readonly string $rule,
        public readonly string $actorType,
        public readonly string $actorLabel,
        public readonly int $count,
        public readonly string $windowStart,
        public readonly string $windowEnd,
        public readonly string $description,
        public readonly string $severity = self::SEVERITY_MEDIUM,
    ) {}
}
