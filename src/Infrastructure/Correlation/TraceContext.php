<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Correlation;

/**
 * Holds the distributed-trace id (W3C traceparent) of the unit of work in
 * flight, so audit records can be joined to the matching APM trace. Pushed at
 * the root of a unit of work and popped when it ends; null means the request
 * carried no trace. A stack supports nested units (a job within a request).
 *
 * @internal
 */
final class TraceContext
{
    /** @var list<?string> */
    private array $stack = [];

    public function push(?string $traceId): void
    {
        $this->stack[] = $traceId;
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
