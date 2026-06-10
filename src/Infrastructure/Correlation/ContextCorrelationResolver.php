<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Correlation;

use Yammi\AuditLog\Application\Contract\CorrelationResolver;

final class ContextCorrelationResolver implements CorrelationResolver
{
    public function __construct(
        private readonly CorrelationContext $context,
    ) {}

    public function resolve(): ?string
    {
        return $this->context->current();
    }
}
