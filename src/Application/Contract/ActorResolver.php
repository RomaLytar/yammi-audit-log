<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract;

use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

interface ActorResolver
{
    public function resolve(): Actor;

    public function resolveOrigin(): ?Actor;

    /**
     * How deep the current unit of work is nested (0 = the root request or
     * command, +1 per job inside a job).
     */
    public function depth(): int;
}
