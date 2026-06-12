<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Support;

use Illuminate\Config\Repository as ConfigRepository;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Support\AuditTimezone;

final class AuditTimezoneTest extends TestCase
{
    public function test_a_configured_zone_wins(): void
    {
        $timezone = new AuditTimezone(new ConfigRepository([
            'audit-log' => ['timezone' => 'Asia/Tokyo'],
            'app' => ['timezone' => 'Europe/Kyiv'],
        ]));

        $this->assertSame('Asia/Tokyo', $timezone->name());
    }

    public function test_empty_falls_back_to_the_application_timezone(): void
    {
        $timezone = new AuditTimezone(new ConfigRepository([
            'audit-log' => ['timezone' => ''],
            'app' => ['timezone' => 'Europe/Kyiv'],
        ]));

        $this->assertSame('Europe/Kyiv', $timezone->name());
    }

    public function test_invalid_zones_fall_back_to_utc(): void
    {
        $timezone = new AuditTimezone(new ConfigRepository([
            'audit-log' => ['timezone' => 'Mars/Olympus'],
            'app' => ['timezone' => 'Pluto/Plain'],
        ]));

        $this->assertSame('UTC', $timezone->name());
    }
}
