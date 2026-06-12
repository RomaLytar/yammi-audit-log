<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Service\FilterParser;
use Yammi\AuditLog\Tests\Support\FixedClock;

final class FilterParserTest extends TestCase
{
    private FilterParser $parser;

    protected function setUp(): void
    {
        $this->parser = new FilterParser(new FixedClock(new DateTimeImmutable('2026-06-15T12:00:00+00:00')));
    }

    public function test_valid_values_are_kept(): void
    {
        $filters = $this->parser->fromArray([
            'model' => 'App\\Models\\Order',
            'event' => 'updated',
            'actor_type' => 'job',
            'actor' => 'Jane',
            'from' => '2026-01-01',
            'to' => '2026-02-01',
            'search' => 'refund',
            'page' => 3,
        ]);

        $this->assertSame('App\\Models\\Order', $filters->type);
        $this->assertSame('updated', $filters->event);
        $this->assertSame('job', $filters->actorType);
        $this->assertSame('Jane', $filters->actor);
        $this->assertSame('2026-01-01', $filters->from);
        $this->assertSame('2026-02-01', $filters->to);
        $this->assertSame('refund', $filters->search);
        $this->assertSame(3, $filters->page);
        $this->assertFalse($filters->defaultRange);
    }

    public function test_the_record_id_filter_is_parsed_and_bounded(): void
    {
        $this->assertSame('42', $this->parser->fromArray(['id' => ' 42 '])->auditableId);
        $this->assertSame('42', $this->parser->fromArray(['id' => 42])->auditableId);
        $this->assertSame('', $this->parser->fromArray(['id' => ['nope']])->auditableId);
        $this->assertSame('', $this->parser->fromArray([])->auditableId);
        $this->assertSame(64, mb_strlen($this->parser->fromArray(['id' => str_repeat('a', 80)])->auditableId));
        $this->assertTrue($this->parser->fromArray(['id' => '42'])->isActive());
    }

    public function test_an_empty_range_defaults_to_the_current_month(): void
    {
        $filters = $this->parser->fromArray([]);

        $this->assertSame('2026-06-01', $filters->from);
        $this->assertSame('2026-06-15', $filters->to);
        $this->assertTrue($filters->defaultRange);
        $this->assertFalse($filters->isActive());
    }

    public function test_an_invalid_date_falls_back_to_the_default_range(): void
    {
        $filters = $this->parser->fromArray(['from' => 'not-a-date', 'event' => 'bogus', 'page' => -2]);

        $this->assertSame('2026-06-01', $filters->from);
        $this->assertSame('2026-06-15', $filters->to);
        $this->assertSame('', $filters->event);
        $this->assertSame(1, $filters->page);
        $this->assertFalse($filters->isActive());
    }

    public function test_only_a_from_date_runs_until_today_at_most(): void
    {
        $filters = $this->parser->fromArray(['from' => '2026-05-01']);

        $this->assertSame('2026-05-01', $filters->from);
        $this->assertSame('2026-06-15', $filters->to);
        $this->assertTrue($filters->isActive());
    }

    public function test_only_a_to_date_gives_one_month_back(): void
    {
        $filters = $this->parser->fromArray(['to' => '2026-03-15']);

        $this->assertSame('2026-02-15', $filters->from);
        $this->assertSame('2026-03-15', $filters->to);
    }

    public function test_a_range_wider_than_a_year_keeps_the_recent_year(): void
    {
        $filters = $this->parser->fromArray(['from' => '2020-01-01', 'to' => '2026-06-01']);

        $this->assertSame('2025-06-01', $filters->from);
        $this->assertSame('2026-06-01', $filters->to);
    }

    public function test_a_reversed_range_is_swapped_before_capping(): void
    {
        $filters = $this->parser->fromArray(['from' => '2026-06-01', 'to' => '2026-05-01']);

        $this->assertSame('2026-05-01', $filters->from);
        $this->assertSame('2026-06-01', $filters->to);
    }

    public function test_long_text_is_capped(): void
    {
        $filters = $this->parser->fromArray(['search' => str_repeat('x', 400)]);

        $this->assertSame(255, mb_strlen($filters->search));
    }
}
