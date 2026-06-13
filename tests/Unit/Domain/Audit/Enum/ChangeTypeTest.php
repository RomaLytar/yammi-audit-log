<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Domain\Audit\Enum;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;

final class ChangeTypeTest extends TestCase
{
    public function test_it_exposes_string_values(): void
    {
        $this->assertSame('created', ChangeType::Created->value);
        $this->assertSame('updated', ChangeType::Updated->value);
        $this->assertSame('deleted', ChangeType::Deleted->value);
        $this->assertSame('restored', ChangeType::Restored->value);
        $this->assertSame('attached', ChangeType::Attached->value);
        $this->assertSame('detached', ChangeType::Detached->value);
        $this->assertSame('synced', ChangeType::Synced->value);
    }

    public function test_it_recognises_creation_and_deletion(): void
    {
        $this->assertTrue(ChangeType::Created->isCreation());
        $this->assertFalse(ChangeType::Updated->isCreation());

        $this->assertTrue(ChangeType::Deleted->isDeletion());
        $this->assertFalse(ChangeType::Created->isDeletion());
    }

    public function test_it_recognises_pivot_events(): void
    {
        $this->assertTrue(ChangeType::Attached->isPivot());
        $this->assertTrue(ChangeType::Detached->isPivot());
        $this->assertTrue(ChangeType::Synced->isPivot());

        $this->assertFalse(ChangeType::Updated->isPivot());
        $this->assertFalse(ChangeType::Deleted->isPivot());
    }

    public function test_pivot_events_are_neither_creation_nor_deletion(): void
    {
        foreach ([ChangeType::Attached, ChangeType::Detached, ChangeType::Synced] as $type) {
            $this->assertFalse($type->isCreation());
            $this->assertFalse($type->isDeletion());
        }
    }

    public function test_it_builds_a_human_label(): void
    {
        $this->assertSame('Created', ChangeType::Created->label());
        $this->assertSame('Attached', ChangeType::Attached->label());
        $this->assertSame('Detached', ChangeType::Detached->label());
        $this->assertSame('Synced', ChangeType::Synced->label());
    }
}
