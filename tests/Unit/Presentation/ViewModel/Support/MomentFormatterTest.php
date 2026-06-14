<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Presentation\ViewModel\Support;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Presentation\ViewModel\Support\MomentFormatter;

final class MomentFormatterTest extends TestCase
{
    public function test_it_formats_in_utc_by_default(): void
    {
        $this->assertSame(
            '2026-01-01 10:00',
            (new MomentFormatter)->format('2026-01-01T10:00:00+00:00'),
        );
    }

    public function test_it_applies_the_configured_timezone(): void
    {
        $this->assertSame(
            '2026-01-01 10:00',
            (new MomentFormatter('Asia/Tokyo'))->format('2026-01-01T01:00:00+00:00'),
        );
    }

    public function test_an_empty_timezone_is_ignored(): void
    {
        $this->assertSame(
            '2026-01-01 10:00',
            (new MomentFormatter(''))->format('2026-01-01T10:00:00+00:00'),
        );
    }

    public function test_it_honours_a_custom_format(): void
    {
        $this->assertSame(
            '2026-01-01',
            (new MomentFormatter)->format('2026-01-01T10:00:00+00:00', 'Y-m-d'),
        );
    }
}
