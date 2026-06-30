<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Correlation;

use Yammi\AuditLog\Application\Contract\Resolver\SpanResolver;
use Yammi\AuditLog\Domain\Audit\ValueObject\Span;

/** @internal */
final class ContextSpanResolver implements SpanResolver
{
    public function __construct(
        private readonly SpanContext $context,
    ) {}

    public function resolve(): ?Span
    {
        return $this->context->current();
    }
}
