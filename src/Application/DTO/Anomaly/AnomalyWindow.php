<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO\Anomaly;

use DateTimeImmutable;

final class AnomalyWindow
{
    public function __construct(
        public readonly DateTimeImmutable $start,
        public readonly DateTimeImmutable $end,
    ) {}
}
