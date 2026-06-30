<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Http\Middleware;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yammi\AuditLog\Infrastructure\Context\RequestContextHolder;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;
use Yammi\AuditLog\Infrastructure\Correlation\SpanContext;
use Yammi\AuditLog\Infrastructure\Http\Middleware\StartAuditCorrelation;

final class StartAuditCorrelationTest extends TestCase
{
    public function test_a_correlation_is_active_during_the_request_and_gone_after(): void
    {
        $context = new CorrelationContext;
        $spans = new SpanContext;
        $middleware = new StartAuditCorrelation($context, new RequestContextHolder, $spans);

        $seen = null;
        $seenSpan = null;

        $response = $middleware->handle(Request::create('/'), function () use ($context, $spans, &$seen, &$seenSpan) {
            $seen = $context->current();
            $seenSpan = $spans->current();

            return 'ok';
        });

        $this->assertSame('ok', $response);
        $this->assertNotNull($seen);
        $this->assertNotNull($seenSpan);
        $this->assertTrue($seenSpan->isRoot());
        $this->assertNull($context->current());
        $this->assertNull($spans->current());
    }

    public function test_the_correlation_is_popped_even_when_the_request_fails(): void
    {
        $context = new CorrelationContext;
        $spans = new SpanContext;
        $middleware = new StartAuditCorrelation($context, new RequestContextHolder, $spans);

        try {
            $middleware->handle(Request::create('/'), static function (): never {
                throw new RuntimeException('boom');
            });

            $this->fail('Expected the exception to propagate.');
        } catch (RuntimeException) {
        }

        $this->assertNull($context->current());
        $this->assertNull($spans->current());
    }
}
