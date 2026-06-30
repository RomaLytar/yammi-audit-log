<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract\Resolver;

/**
 * Resolves the distributed-trace id (W3C traceparent) of the current unit of
 * work, so each change can be linked back to the APM trace that caused it.
 */
interface TraceResolver
{
    public function resolve(): ?string;
}
