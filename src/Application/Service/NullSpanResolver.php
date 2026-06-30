<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service;

use Yammi\AuditLog\Application\Contract\Resolver\SpanResolver;
use Yammi\AuditLog\Domain\Audit\ValueObject\Span;

/** @internal */
final class NullSpanResolver implements SpanResolver
{
    public function resolve(): ?Span
    {
        return null;
    }
}
