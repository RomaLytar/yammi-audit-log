<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Console\Support;

use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\PruneAuditLogAction;
use Yammi\AuditLog\Infrastructure\Console\Support\RetentionWindow;

final class RetentionWindowTest extends TestCase
{
    public function test_a_numeric_option_overrides_the_configured_window(): void
    {
        $config = new Repository(['audit-log' => ['retention' => ['days' => 180]]]);

        $this->assertSame(7, (new RetentionWindow)->days('7', $config));
    }

    public function test_it_falls_back_to_the_configured_window(): void
    {
        $config = new Repository(['audit-log' => ['retention' => ['days' => 90]]]);

        $this->assertSame(90, (new RetentionWindow)->days(null, $config));
    }

    public function test_it_falls_back_to_the_default_when_unset(): void
    {
        $this->assertSame(
            PruneAuditLogAction::DEFAULT_DAYS,
            (new RetentionWindow)->days(null, new Repository([])),
        );
    }

    public function test_a_non_numeric_configured_value_falls_back_to_the_default(): void
    {
        $config = new Repository(['audit-log' => ['retention' => ['days' => 'soon']]]);

        $this->assertSame(PruneAuditLogAction::DEFAULT_DAYS, (new RetentionWindow)->days(null, $config));
    }
}
