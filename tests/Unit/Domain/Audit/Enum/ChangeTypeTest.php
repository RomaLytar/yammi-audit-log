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
    }

    public function test_it_recognises_creation_and_deletion(): void
    {
        $this->assertTrue(ChangeType::Created->isCreation());
        $this->assertFalse(ChangeType::Updated->isCreation());

        $this->assertTrue(ChangeType::Deleted->isDeletion());
        $this->assertFalse(ChangeType::Created->isDeletion());
    }

    public function test_it_builds_a_human_label(): void
    {
        $this->assertSame('Created', ChangeType::Created->label());
    }
}
