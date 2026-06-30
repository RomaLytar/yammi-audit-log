<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract\Resolver;

use Yammi\AuditLog\Domain\Audit\ValueObject\Span;

/**
 * Resolves the span of the current unit of work: its own id and the id of the
 * span that caused it. Stamped onto every change so the records of one
 * correlation can be reassembled into a causation tree.
 */
interface SpanResolver
{
    public function resolve(): ?Span;
}
