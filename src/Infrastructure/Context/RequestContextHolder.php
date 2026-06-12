<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Context;

use Illuminate\Http\Request;

/**
 * Holds the request currently being handled, captured by the correlation
 * middleware, so changes made outside HTTP (console, queue workers) never
 * pick up stale or synthetic request metadata.
 *
 * @internal
 */
final class RequestContextHolder
{
    private ?Request $request = null;

    public function capture(Request $request): void
    {
        $this->request = $request;
    }

    public function clear(): void
    {
        $this->request = null;
    }

    public function current(): ?Request
    {
        return $this->request;
    }
}
