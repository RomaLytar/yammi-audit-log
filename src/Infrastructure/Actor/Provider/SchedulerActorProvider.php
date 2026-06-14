<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Actor\Provider;

use Yammi\AuditLog\Application\Contract\Actor\ActorProvider;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Infrastructure\Actor\ActorContext;

/** @internal */
final class SchedulerActorProvider implements ActorProvider
{
    public function __construct(
        private readonly ActorContext $context,
    ) {}

    public function resolve(): ?Actor
    {
        $task = $this->context->currentScheduledTask();

        return $task === null ? null : Actor::scheduler($task);
    }
}
