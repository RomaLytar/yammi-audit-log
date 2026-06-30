<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Correlation;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Correlation\TraceParentParser;

final class TraceParentParserTest extends TestCase
{
    public function test_it_extracts_the_trace_id_from_a_valid_header(): void
    {
        $this->assertSame(
            '4bf92f3577b34da6a3ce929d0e0e4736',
            (new TraceParentParser)->traceId('00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01'),
        );
    }

    public function test_it_lowercases_and_tolerates_future_trailing_fields(): void
    {
        $this->assertSame(
            '4bf92f3577b34da6a3ce929d0e0e4736',
            (new TraceParentParser)->traceId('cd-4BF92F3577B34DA6A3CE929D0E0E4736-00F067AA0BA902B7-09-future'),
        );
    }

    public function test_null_malformed_and_all_zero_trace_ids_yield_null(): void
    {
        $parser = new TraceParentParser;

        $this->assertNull($parser->traceId(null));
        $this->assertNull($parser->traceId('not-a-traceparent'));
        $this->assertNull($parser->traceId('00-00000000000000000000000000000000-00f067aa0ba902b7-01'));
    }
}
