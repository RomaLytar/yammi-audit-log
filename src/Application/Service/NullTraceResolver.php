<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service;

use Yammi\AuditLog\Application\Contract\Resolver\TraceResolver;

/** @internal */
final class NullTraceResolver implements TraceResolver
{
    public function resolve(): ?string
    {
        return null;
    }
}
