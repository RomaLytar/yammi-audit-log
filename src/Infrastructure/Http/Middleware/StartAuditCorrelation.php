<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yammi\AuditLog\Infrastructure\Context\RequestContextHolder;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;

/** @internal */
final class StartAuditCorrelation
{
    public function __construct(
        private readonly CorrelationContext $context,
        private readonly RequestContextHolder $requestContext,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $this->context->push((string) Str::uuid());
        $this->requestContext->capture($request);

        try {
            return $next($request);
        } finally {
            $this->context->pop();
            $this->requestContext->clear();
        }
    }
}
