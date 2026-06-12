<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Context;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Context\HttpRequestContextResolver;
use Yammi\AuditLog\Infrastructure\Context\RequestContextHolder;

final class HttpRequestContextResolverTest extends TestCase
{
    public function test_no_held_request_means_no_context(): void
    {
        $resolver = new HttpRequestContextResolver(new RequestContextHolder);

        $this->assertSame([], $resolver->resolve());
    }

    public function test_the_held_request_is_summarised(): void
    {
        $holder = new RequestContextHolder;
        $holder->capture(Request::create('https://shop.test/orders/42?force=1', 'POST', server: [
            'HTTP_USER_AGENT' => 'TestAgent/1.0',
        ]));

        $context = (new HttpRequestContextResolver($holder))->resolve();

        $this->assertSame('https://shop.test/orders/42?force=1', $context['url']);
        $this->assertSame('POST', $context['method']);
        $this->assertSame('TestAgent/1.0', $context['user_agent']);

        $holder->clear();
        $this->assertSame([], (new HttpRequestContextResolver($holder))->resolve());
    }
}
