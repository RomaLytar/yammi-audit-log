<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Correlation;

use Yammi\AuditLog\Domain\Audit\ValueObject\Span;

/**
 * Holds the span of the unit of work currently in flight. Pushed at the root of
 * a unit of work (request, command or job) and popped when it ends; a stack
 * supports nested units (a job dispatched within a request).
 *
 * @internal
 */
final class SpanContext
{
    /** @var list<Span> */
    private array $stack = [];

    public function push(Span $span): void
    {
        $this->stack[] = $span;
    }

    public function pop(): void
    {
        array_pop($this->stack);
    }

    public function current(): ?Span
    {
        $key = array_key_last($this->stack);

        return $key === null ? null : $this->stack[$key];
    }
}
