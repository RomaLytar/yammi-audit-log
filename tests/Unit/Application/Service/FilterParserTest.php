<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Service;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Service\FilterParser;

final class FilterParserTest extends TestCase
{
    public function test_valid_values_are_kept(): void
    {
        $filters = (new FilterParser)->fromArray([
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
    }

    public function test_invalid_values_are_dropped(): void
    {
        $filters = (new FilterParser)->fromArray([
            'event' => 'bogus',
            'actor_type' => ['array'],
            'from' => 'not-a-date',
            'page' => -2,
            'unknown' => 'ignored',
        ]);

        $this->assertSame('', $filters->event);
        $this->assertSame('', $filters->actorType);
        $this->assertSame('', $filters->from);
        $this->assertSame(1, $filters->page);
        $this->assertFalse($filters->isActive());
    }

    public function test_long_text_is_capped(): void
    {
        $filters = (new FilterParser)->fromArray(['search' => str_repeat('x', 400)]);

        $this->assertSame(255, mb_strlen($filters->search));
    }
}
