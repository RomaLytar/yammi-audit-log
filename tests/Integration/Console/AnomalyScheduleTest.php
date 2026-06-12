<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Console;

use Illuminate\Console\Scheduling\Schedule;
use Yammi\AuditLog\Tests\TestCase;

final class AnomalyScheduleTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.anomalies.cron', '0 * * * *');
    }

    public function test_the_scan_is_scheduled_when_a_cron_is_configured(): void
    {
        $events = $this->app->make(Schedule::class)->events();

        $scheduled = array_filter(
            $events,
            static fn ($event): bool => str_contains((string) $event->command, 'audit-log:detect-anomalies'),
        );

        $this->assertCount(1, $scheduled);
        $this->assertSame('0 * * * *', array_values($scheduled)[0]->expression);
    }
}
