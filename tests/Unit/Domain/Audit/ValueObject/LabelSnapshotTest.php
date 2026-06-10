<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Domain\Audit\ValueObject;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;

final class LabelSnapshotTest extends TestCase
{
    public function test_empty_snapshot_returns_null_labels(): void
    {
        $snapshot = LabelSnapshot::empty();

        $this->assertTrue($snapshot->isEmpty());
        $this->assertNull($snapshot->for('user_id'));
    }

    public function test_it_caps_overlong_label_values(): void
    {
        $snapshot = new LabelSnapshot(['user_id' => str_repeat('a', 300)]);

        $this->assertSame(191, mb_strlen((string) $snapshot->for('user_id')));
    }

    public function test_it_returns_the_captured_label_for_a_field(): void
    {
        $snapshot = new LabelSnapshot(['user_id' => 'John Doe', 'order_id' => 'Order #1024']);

        $this->assertFalse($snapshot->isEmpty());
        $this->assertSame('John Doe', $snapshot->for('user_id'));
        $this->assertSame(['user_id' => 'John Doe', 'order_id' => 'Order #1024'], $snapshot->all());
    }
}
