<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ConfigDefaultsTest extends TestCase
{
    public function test_the_bundled_dashboard_is_off_by_default(): void
    {
        $this->assertFalse($this->config()['ui']['enabled']);
    }

    public function test_capture_and_retention_defaults_stay_safe(): void
    {
        $config = $this->config();

        $this->assertTrue($config['enabled']);
        $this->assertSame(180, $config['retention']['days']);
        $this->assertFalse($config['write']['async']);
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return require __DIR__.'/../../config/audit-log.php';
    }
}
