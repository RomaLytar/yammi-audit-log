<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Support;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Support\SystemClock;

final class SystemClockTest extends TestCase
{
    public function test_now_returns_the_current_time(): void
    {
        $before = time();
        $now = (new SystemClock)->now()->getTimestamp();
        $after = time();

        $this->assertGreaterThanOrEqual($before, $now);
        $this->assertLessThanOrEqual($after, $now);
    }
}
