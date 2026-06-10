<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Correlation;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Correlation\ContextCorrelationResolver;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;

final class ContextCorrelationResolverTest extends TestCase
{
    public function test_it_resolves_the_current_correlation(): void
    {
        $context = new CorrelationContext;
        $resolver = new ContextCorrelationResolver($context);

        $this->assertNull($resolver->resolve());

        $context->push('corr-1');

        $this->assertSame('corr-1', $resolver->resolve());
    }
}
