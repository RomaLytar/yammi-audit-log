<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Console;

use Illuminate\Console\Scheduling\Schedule;
use Yammi\AuditLog\Tests\TestCase;

final class RetentionDefaultsTest extends TestCase
{
    public function test_retention_defaults_to_180_days(): void
    {
        $this->assertSame(180, $this->app['config']->get('audit-log.retention.days'));
    }

    public function test_pruning_is_scheduled_out_of_the_box(): void
    {
        $events = $this->app->make(Schedule::class)->events();

        $scheduled = array_filter(
            $events,
            static fn ($event): bool => str_contains((string) $event->command, 'audit-log:prune'),
        );

        $this->assertCount(1, $scheduled);
    }
}
