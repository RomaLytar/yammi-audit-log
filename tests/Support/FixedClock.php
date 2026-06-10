<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use DateTimeImmutable;
use Yammi\AuditLog\Application\Contract\Clock;

final class FixedClock implements Clock
{
    public function __construct(
        private readonly DateTimeImmutable $now,
    ) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
