<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Support;

use DateTimeImmutable;
use Yammi\AuditLog\Application\Contract\Clock;

/** @internal */
final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable;
    }
}
