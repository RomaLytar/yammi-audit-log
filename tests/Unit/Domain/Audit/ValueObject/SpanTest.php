<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Domain\Audit\ValueObject;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;
use Yammi\AuditLog\Domain\Audit\ValueObject\Span;

final class SpanTest extends TestCase
{
    public function test_a_span_without_a_parent_is_a_root(): void
    {
        $span = new Span('span-1');

        $this->assertSame('span-1', $span->id);
        $this->assertNull($span->parentId);
        $this->assertTrue($span->isRoot());
    }

    public function test_a_span_with_a_parent_is_not_a_root(): void
    {
        $span = new Span('span-2', 'span-1');

        $this->assertSame('span-1', $span->parentId);
        $this->assertFalse($span->isRoot());
    }

    public function test_it_trims_the_id_and_normalises_a_blank_parent_to_null(): void
    {
        $span = new Span('  span-3  ', '   ');

        $this->assertSame('span-3', $span->id);
        $this->assertNull($span->parentId);
    }

    public function test_it_rejects_an_empty_id(): void
    {
        $this->expectException(InvalidAuditData::class);

        new Span('   ');
    }

    public function test_equality_is_by_value(): void
    {
        $this->assertTrue((new Span('a', 'b'))->equals(new Span('a', 'b')));
        $this->assertFalse((new Span('a', 'b'))->equals(new Span('a', 'c')));
        $this->assertFalse((new Span('a'))->equals(new Span('b')));
    }
}
