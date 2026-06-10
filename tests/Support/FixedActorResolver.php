<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use Yammi\AuditLog\Application\Contract\ActorResolver;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

final class FixedActorResolver implements ActorResolver
{
    public function __construct(
        private readonly Actor $actor,
        private readonly ?Actor $origin = null,
    ) {}

    public function resolve(): Actor
    {
        return $this->actor;
    }

    public function resolveOrigin(): ?Actor
    {
        return $this->origin;
    }
}
