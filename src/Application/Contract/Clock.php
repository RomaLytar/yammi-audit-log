<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
