<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Http;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Http\FilterFactory;

final class FilterFactoryTest extends TestCase
{
    public function test_it_drops_invalid_filter_values(): void
    {
        $request = Request::create('/audit-log', 'GET', [
            'type' => 'App\\Models\\Order',
            'event' => 'bogus',
            'actor_type' => 'martian',
            'from' => 'not-a-date',
            'to' => '2026-06-10',
            'page' => '-5',
        ]);

        $filters = (new FilterFactory)->fromRequest($request);

        $this->assertSame('App\\Models\\Order', $filters->type);
        $this->assertSame('', $filters->event);
        $this->assertSame('', $filters->actorType);
        $this->assertSame('', $filters->from);
        $this->assertSame('2026-06-10', $filters->to);
        $this->assertSame(1, $filters->page);
    }

    public function test_it_keeps_valid_values(): void
    {
        $request = Request::create('/audit-log', 'GET', [
            'event' => 'updated',
            'actor_type' => 'job',
            'page' => '3',
        ]);

        $filters = (new FilterFactory)->fromRequest($request);

        $this->assertSame('updated', $filters->event);
        $this->assertSame('job', $filters->actorType);
        $this->assertSame(3, $filters->page);
    }
}
