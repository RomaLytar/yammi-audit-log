<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Pipeline\Stage;

use Yammi\AuditLog\Application\Contract\ActorResolver;
use Yammi\AuditLog\Application\Pipeline\RecordChangeContext;
use Yammi\AuditLog\Application\Pipeline\RecordChangeStage;

final class ResolveActorStage implements RecordChangeStage
{
    public function __construct(
        private readonly ActorResolver $resolver,
    ) {}

    public function __invoke(RecordChangeContext $context): RecordChangeContext
    {
        return $context->withActor(
            $this->resolver->resolve(),
            $this->resolver->resolveOrigin(),
        );
    }
}
