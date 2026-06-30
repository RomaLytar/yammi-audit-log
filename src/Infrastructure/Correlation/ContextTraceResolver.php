<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Correlation;

use Yammi\AuditLog\Application\Contract\Resolver\TraceResolver;

/** @internal */
final class ContextTraceResolver implements TraceResolver
{
    public function __construct(
        private readonly TraceContext $context,
    ) {}

    public function resolve(): ?string
    {
        return $this->context->current();
    }
}
