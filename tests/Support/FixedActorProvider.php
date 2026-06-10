<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use Yammi\AuditLog\Application\Contract\ActorProvider;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

final class FixedActorProvider implements ActorProvider
{
    public function __construct(
        private readonly ?Actor $actor,
    ) {}

    public function resolve(): ?Actor
    {
        return $this->actor;
    }
}
