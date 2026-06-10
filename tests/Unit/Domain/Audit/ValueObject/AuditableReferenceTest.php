<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Domain\Audit\ValueObject;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

final class AuditableReferenceTest extends TestCase
{
    public function test_it_normalises_integer_ids_to_strings(): void
    {
        $ref = AuditableReference::to('App\\Models\\Order', 1024);

        $this->assertSame('App\\Models\\Order', $ref->type);
        $this->assertSame('1024', $ref->id);
    }

    public function test_it_rejects_an_empty_type(): void
    {
        $this->expectException(InvalidAuditData::class);

        new AuditableReference('', '1');
    }

    public function test_it_rejects_an_empty_id(): void
    {
        $this->expectException(InvalidAuditData::class);

        AuditableReference::to('App\\Models\\Order', '');
    }

    public function test_equality_is_by_value(): void
    {
        $this->assertTrue(
            AuditableReference::to('Order', 1)->equals(AuditableReference::to('Order', '1')),
        );
        $this->assertFalse(
            AuditableReference::to('Order', 1)->equals(AuditableReference::to('Order', 2)),
        );
    }
}
