<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Http;

use DateTimeImmutable;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Service\FilterParser;
use Yammi\AuditLog\Infrastructure\Http\FilterFactory;
use Yammi\AuditLog\Tests\Support\FixedClock;

final class FilterFactoryTest extends TestCase
{
    private FilterFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new FilterFactory(
            new FilterParser(new FixedClock(new DateTimeImmutable('2026-06-15T12:00:00+00:00'))),
        );
    }

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

        $filters = $this->factory->fromRequest($request);

        $this->assertSame('App\\Models\\Order', $filters->type);
        $this->assertSame('', $filters->event);
        $this->assertSame('', $filters->actorType);
        $this->assertSame('2026-05-10', $filters->from);
        $this->assertSame('2026-06-10', $filters->to);
        $this->assertSame(1, $filters->page);
    }

    public function test_it_keeps_valid_values_and_defaults_the_range(): void
    {
        $request = Request::create('/audit-log', 'GET', [
            'event' => 'updated',
            'actor_type' => 'job',
            'page' => '3',
        ]);

        $filters = $this->factory->fromRequest($request);

        $this->assertSame('updated', $filters->event);
        $this->assertSame('job', $filters->actorType);
        $this->assertSame(3, $filters->page);
        $this->assertSame('2026-06-01', $filters->from);
        $this->assertSame('2026-06-15', $filters->to);
        $this->assertTrue($filters->defaultRange);
    }

    public function test_the_search_term_is_parsed_and_capped(): void
    {
        $request = Request::create('/audit-log', 'GET', ['q' => str_repeat('x', 300)]);

        $filters = $this->factory->fromRequest($request);

        $this->assertSame(255, mb_strlen($filters->search));
        $this->assertTrue($filters->isActive());
    }

    public function test_a_non_string_search_is_dropped(): void
    {
        $request = Request::create('/audit-log', 'GET', ['q' => ['array']]);

        $this->assertSame('', $this->factory->fromRequest($request)->search);
    }
}
