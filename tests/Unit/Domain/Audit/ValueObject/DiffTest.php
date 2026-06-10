<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Domain\Audit\ValueObject;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\FieldDiff;

final class DiffTest extends TestCase
{
    public function test_empty_diff_has_no_fields(): void
    {
        $diff = Diff::empty();

        $this->assertTrue($diff->isEmpty());
        $this->assertSame(0, $diff->count());
    }

    public function test_between_keeps_only_changed_fields(): void
    {
        $diff = Diff::between(
            ['name' => 'Jane', 'status' => 'active', 'age' => 30],
            ['name' => 'Jane', 'status' => 'blocked', 'age' => 31],
        );

        $this->assertFalse($diff->isEmpty());
        $this->assertSame(2, $diff->count());
        $this->assertTrue($diff->has('status'));
        $this->assertFalse($diff->has('name'));

        $status = $diff->field('status');
        $this->assertInstanceOf(FieldDiff::class, $status);
        $this->assertSame('active', $status->old);
        $this->assertSame('blocked', $status->new);
    }

    public function test_between_detects_added_and_removed_keys(): void
    {
        $diff = Diff::between(
            ['removed' => 'x'],
            ['added' => 'y'],
        );

        $this->assertSame(2, $diff->count());
        $this->assertSame('x', $diff->field('removed')?->old);
        $this->assertNull($diff->field('removed')?->new);
        $this->assertNull($diff->field('added')?->old);
        $this->assertSame('y', $diff->field('added')?->new);
    }

    public function test_between_compares_arrays_by_value(): void
    {
        $diff = Diff::between(
            ['meta' => ['a' => 1]],
            ['meta' => ['a' => 1]],
        );

        $this->assertTrue($diff->isEmpty());
    }

    public function test_it_serialises_to_an_old_new_array(): void
    {
        $diff = Diff::between(['n' => 1], ['n' => 2]);

        $this->assertSame(['n' => ['old' => 1, 'new' => 2]], $diff->toArray());
    }

    public function test_from_fields_indexes_by_field_name(): void
    {
        $diff = Diff::fromFields([
            new FieldDiff('a', 1, 2),
            new FieldDiff('b', null, 'x'),
        ]);

        $this->assertSame(2, $diff->count());
        $this->assertSame(2, $diff->field('a')?->new);
    }
}
