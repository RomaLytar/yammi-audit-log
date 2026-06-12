<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Http\Middleware;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yammi\AuditLog\Infrastructure\Context\RequestContextHolder;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;
use Yammi\AuditLog\Infrastructure\Http\Middleware\StartAuditCorrelation;

final class StartAuditCorrelationTest extends TestCase
{
    public function test_a_correlation_is_active_during_the_request_and_gone_after(): void
    {
        $context = new CorrelationContext;
        $middleware = new StartAuditCorrelation($context, new RequestContextHolder);

        $seen = null;

        $response = $middleware->handle(Request::create('/'), function () use ($context, &$seen) {
            $seen = $context->current();

            return 'ok';
        });

        $this->assertSame('ok', $response);
        $this->assertNotNull($seen);
        $this->assertNull($context->current());
    }

    public function test_the_correlation_is_popped_even_when_the_request_fails(): void
    {
        $context = new CorrelationContext;
        $middleware = new StartAuditCorrelation($context, new RequestContextHolder);

        try {
            $middleware->handle(Request::create('/'), static function (): never {
                throw new RuntimeException('boom');
            });

            $this->fail('Expected the exception to propagate.');
        } catch (RuntimeException) {
        }

        $this->assertNull($context->current());
    }
}
