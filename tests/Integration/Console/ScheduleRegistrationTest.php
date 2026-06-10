<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Console;

use Illuminate\Console\Scheduling\Schedule;
use Yammi\AuditLog\Tests\TestCase;

final class ScheduleRegistrationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.retention.days', 30);
        $app['config']->set('audit-log.retention.schedule.enabled', true);
        $app['config']->set('audit-log.retention.schedule.cron', '0 4 * * *');
    }

    public function test_the_prune_command_is_scheduled(): void
    {
        $events = $this->app->make(Schedule::class)->events();

        $scheduled = array_filter(
            $events,
            static fn ($event): bool => str_contains((string) $event->command, 'audit-log:prune'),
        );

        $this->assertCount(1, $scheduled);
        $this->assertSame('0 4 * * *', array_values($scheduled)[0]->expression);
    }
}
