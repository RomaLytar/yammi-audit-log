<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;

final class StartAuditCorrelation
{
    public function __construct(
        private readonly CorrelationContext $context,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $this->context->push((string) Str::uuid());

        try {
            return $next($request);
        } finally {
            $this->context->pop();
        }
    }
}
