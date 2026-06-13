<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Provider;

use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Yammi\AuditLog\Infrastructure\Console\DetectAnomaliesCommand;
use Yammi\AuditLog\Infrastructure\Console\GenerateDigestCommand;
use Yammi\AuditLog\Infrastructure\Console\PruneAuditLogCommand;

/**
 * Registers the package's scheduled commands (prune, anomaly scan, digest).
 * The provider hands in its own callAfterResolving so the callbacks attach to
 * the application's Schedule singleton at the right moment, even on the older
 * Laravel versions where booting and resolving order differs.
 *
 * @internal
 */
final class ScheduleRegistrar
{
    /**
     * @param  Closure(string, callable): void  $callAfterResolving
     */
    public function __construct(
        private readonly Closure $callAfterResolving,
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

        $this->schedule(
            PruneAuditLogCommand::class,
            (string) $config->get('audit-log.retention.schedule.cron', '0 3 * * *'),
            'audit-log:prune',
        );
    }

    private function registerAnomalyScan(ConfigRepository $config): void
    {
        $cron = $config->get('audit-log.anomalies.cron');

        if (! is_string($cron) || trim($cron) === '') {
            return;
        }

        $this->schedule(DetectAnomaliesCommand::class, trim($cron), 'audit-log:detect-anomalies');
    }

    private function registerDigest(ConfigRepository $config): void
    {
        $cron = $config->get('audit-log.integrity.digest_cron');

        if (! is_string($cron) || trim($cron) === '') {
            return;
        }

        $this->schedule(GenerateDigestCommand::class, trim($cron), 'audit-log:digest');
    }

    private function schedule(string $command, string $cron, string $name): void
    {
        ($this->callAfterResolving)(Schedule::class, static function (Schedule $schedule) use ($command, $cron, $name): void {
            $schedule->command($command)
                ->cron($cron)
                ->name($name)
                ->withoutOverlapping();
        });
    }
}
