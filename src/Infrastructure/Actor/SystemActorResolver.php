<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Actor;

use Yammi\AuditLog\Application\Contract\ActorResolver;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

/**
 * Placeholder resolver used until the multi-level resolver chain is wired.
 */
final class SystemActorResolver implements ActorResolver
{
    public function resolve(): Actor
    {
        return Actor::system();
    }

    public function resolveOrigin(): ?Actor
    {
        return null;
    }
}
