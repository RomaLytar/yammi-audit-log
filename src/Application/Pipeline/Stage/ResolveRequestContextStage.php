<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Pipeline\Stage;

use Yammi\AuditLog\Application\Contract\Resolver\RequestContextResolver;
use Yammi\AuditLog\Application\Pipeline\RecordChangeContext;
use Yammi\AuditLog\Application\Pipeline\RecordChangeStage;

/** @internal */
final class ResolveRequestContextStage implements RecordChangeStage
{
    public function __construct(
        private readonly RequestContextResolver $resolver,
    ) {}

    public function __invoke(RecordChangeContext $context): RecordChangeContext
    {
        return $context->withRequestContext($this->resolver->resolve());
    }
}
