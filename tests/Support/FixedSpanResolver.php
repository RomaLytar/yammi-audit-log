<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use Yammi\AuditLog\Application\Contract\Resolver\SpanResolver;
use Yammi\AuditLog\Domain\Audit\ValueObject\Span;

final class FixedSpanResolver implements SpanResolver
{
    public function __construct(
        private readonly ?Span $span = null,
    ) {}

    public function resolve(): ?Span
    {
        return $this->span;
    }
}
