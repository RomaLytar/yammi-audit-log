<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Domain\Audit\ValueObject;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;
use Yammi\AuditLog\Domain\Audit\ValueObject\FieldDiff;

final class FieldDiffTest extends TestCase
{
    public function test_an_empty_field_name_is_rejected(): void
    {
        $this->expectException(InvalidAuditData::class);

        new FieldDiff('', 'a', 'b');
    }

    public function test_changed_reflects_value_difference(): void
    {
        $this->assertTrue((new FieldDiff('status', 'a', 'b'))->changed());
        $this->assertFalse((new FieldDiff('status', 'a', 'a'))->changed());
    }

    public function test_short_values_are_kept_as_is(): void
    {
        $diff = new FieldDiff('meta', ['a' => 1], 'text');

        $this->assertSame(['a' => 1], $diff->old);
        $this->assertSame('text', $diff->new);
    }

    public function test_an_oversized_string_is_truncated(): void
    {
        $diff = new FieldDiff('body', str_repeat('x', 70000), null);

        $this->assertIsString($diff->old);
        $this->assertLessThan(70000, mb_strlen($diff->old));
        $this->assertStringEndsWith('… (truncated)', $diff->old);
    }

    public function test_an_oversized_array_is_truncated_to_a_string(): void
    {
        $huge = ['items' => array_fill(0, 10000, str_repeat('y', 20))];

        $diff = new FieldDiff('payload', $huge, null);

        $this->assertIsString($diff->old);
        $this->assertLessThanOrEqual(65535 + mb_strlen('… (truncated)'), mb_strlen($diff->old));
        $this->assertStringEndsWith('… (truncated)', $diff->old);
    }

    public function test_a_small_array_is_not_serialized(): void
    {
        $diff = new FieldDiff('meta', null, ['a' => 1, 'b' => [2, 3]]);

        $this->assertSame(['a' => 1, 'b' => [2, 3]], $diff->new);
    }
}
