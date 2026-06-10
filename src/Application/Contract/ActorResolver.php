<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract;

use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

interface ActorResolver
{
    public function resolve(): Actor;

    public function resolveOrigin(): ?Actor;
}
