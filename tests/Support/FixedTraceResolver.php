<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use Yammi\AuditLog\Application\Contract\Resolver\TraceResolver;

final class FixedTraceResolver implements TraceResolver
{
    public function __construct(
        private readonly ?string $traceId = null,
    ) {}

    public function resolve(): ?string
    {
        return $this->traceId;
    }
}
