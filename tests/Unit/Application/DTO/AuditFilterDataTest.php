<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\DTO;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\AuditFilterData;

final class AuditFilterDataTest extends TestCase
{
    public function test_defaults_are_inactive(): void
    {
        $this->assertFalse((new AuditFilterData)->isActive());
    }

    public function test_any_filled_filter_makes_it_active(): void
    {
        $this->assertTrue((new AuditFilterData(type: 'App\\Models\\Order'))->isActive());
        $this->assertTrue((new AuditFilterData(event: 'updated'))->isActive());
        $this->assertTrue((new AuditFilterData(actorType: 'user'))->isActive());
        $this->assertTrue((new AuditFilterData(actor: 'Jane'))->isActive());
        $this->assertTrue((new AuditFilterData(from: '2026-01-01'))->isActive());
        $this->assertTrue((new AuditFilterData(to: '2026-01-01'))->isActive());
    }

    public function test_the_page_alone_does_not_make_it_active(): void
    {
        $this->assertFalse((new AuditFilterData(page: 3))->isActive());
    }
}
