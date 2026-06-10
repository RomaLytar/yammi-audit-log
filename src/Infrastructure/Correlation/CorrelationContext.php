<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Correlation;

/**
 * Holds the correlation id of the unit of work currently in flight. Pushed at
 * the root of a unit of work (request, command or job) and popped when it ends;
 * a stack supports nested units (a job dispatched within a request).
 */
final class CorrelationContext
{
    /** @var list<string> */
    private array $stack = [];

    public function push(string $correlationId): void
    {
        $this->stack[] = $correlationId;
    }

    public function pop(): void
    {
        array_pop($this->stack);
    }

    public function current(): ?string
    {
        $key = array_key_last($this->stack);

        return $key === null ? null : $this->stack[$key];
    }
}
