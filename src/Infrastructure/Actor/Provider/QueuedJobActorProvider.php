<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Actor\Provider;

use Yammi\AuditLog\Application\Contract\ActorProvider;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Infrastructure\Actor\ActorContext;

/** @internal */
final class QueuedJobActorProvider implements ActorProvider
{
    public function __construct(
        private readonly ActorContext $context,
    ) {}

    public function resolve(): ?Actor
    {
        $job = $this->context->currentJob();

        return $job === null ? null : Actor::job($job);
    }
}
