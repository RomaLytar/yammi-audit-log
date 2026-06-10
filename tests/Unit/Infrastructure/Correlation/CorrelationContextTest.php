<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Correlation;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;

final class CorrelationContextTest extends TestCase
{
    public function test_it_tracks_the_current_correlation_as_a_stack(): void
    {
        $context = new CorrelationContext;

        $this->assertNull($context->current());

        $context->push('request-1');
        $this->assertSame('request-1', $context->current());

        $context->push('job-2');
        $this->assertSame('job-2', $context->current());

        $context->pop();
        $this->assertSame('request-1', $context->current());

        $context->pop();
        $this->assertNull($context->current());
    }
}
