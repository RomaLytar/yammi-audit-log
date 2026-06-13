<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Provider;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Yammi\AuditLog\Infrastructure\Console\DetectAnomaliesCommand;
use Yammi\AuditLog\Infrastructure\Console\GenerateDigestCommand;
use Yammi\AuditLog\Infrastructure\Console\PruneAuditLogCommand;

/**
 * Registers the package's scheduled commands (prune, anomaly scan, digest) via
 * callAfterResolving so they attach to the application's Schedule singleton.
 *
 * @internal
 */
final class ScheduleRegistrar
{
    public function __construct(
        private readonly Application $app,
    ) {}

    public function register(ConfigRepository $config): void
    {
        $this->registerRetention($config);
        $this->registerAnomalyScan($config);
        $this->registerDigest($config);
    }

    private function registerRetention(ConfigRepository $config): void
    {
        $days = (int) $config->get('audit-log.retention.days', 0);

        if ($days <= 0 || ! (bool) $config->get('audit-log.retention.schedule.enabled', true)) {
            return;
        }

        $this->app->callAfterResolving(Schedule::class, static function (Schedule $schedule) use ($config): void {
            $schedule->command(PruneAuditLogCommand::class)
                ->cron((string) $config->get('audit-log.retention.schedule.cron', '0 3 * * *'))
                ->name('audit-log:prune')
                ->withoutOverlapping();
        });
    }

    private function registerAnomalyScan(ConfigRepository $config): void
    {
        $cron = $config->get('audit-log.anomalies.cron');

        if (! is_string($cron) || trim($cron) === '') {
            return;
        }

        $this->app->callAfterResolving(Schedule::class, static function (Schedule $schedule) use ($cron): void {
            $schedule->command(DetectAnomaliesCommand::class)
                ->cron(trim($cron))
                ->name('audit-log:detect-anomalies')
                ->withoutOverlapping();
        });
    }

    private function registerDigest(ConfigRepository $config): void
    {
        $cron = $config->get('audit-log.integrity.digest_cron');

        if (! is_string($cron) || trim($cron) === '') {
            return;
        }

        $this->app->callAfterResolving(Schedule::class, static function (Schedule $schedule) use ($cron): void {
            $schedule->command(GenerateDigestCommand::class)
                ->cron(trim($cron))
                ->name('audit-log:digest')
                ->withoutOverlapping();
        });
    }
}
