<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yammi\AuditLog\Domain\Audit\ValueObject\Span;
use Yammi\AuditLog\Infrastructure\Context\RequestContextHolder;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;
use Yammi\AuditLog\Infrastructure\Correlation\SpanContext;

/** @internal */
final class StartAuditCorrelation
{
    public function __construct(
        private readonly CorrelationContext $context,
        private readonly RequestContextHolder $requestContext,
        private readonly SpanContext $spans,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $this->context->push((string) Str::uuid());
        $this->spans->push(new Span((string) Str::uuid(), $this->spans->current()?->id));
        $this->requestContext->capture($request);

        try {
            return $next($request);
        } finally {
            $this->context->pop();
            $this->spans->pop();
            $this->requestContext->clear();
        }
    }
}
