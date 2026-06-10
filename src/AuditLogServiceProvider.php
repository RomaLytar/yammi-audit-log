<?php

declare(strict_types=1);

namespace Yammi\AuditLog;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Yammi\AuditLog\Application\Contract\ActorResolver;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\Contract\LabelResolver;
use Yammi\AuditLog\Application\Contract\ValueRedactor;
use Yammi\AuditLog\Application\Pipeline\RecordChangePipeline;
use Yammi\AuditLog\Application\Pipeline\Stage\ComputeDiffStage;
use Yammi\AuditLog\Application\Pipeline\Stage\ResolveActorStage;
use Yammi\AuditLog\Application\Pipeline\Stage\ResolveLabelsStage;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Infrastructure\Actor\SystemActorResolver;
use Yammi\AuditLog\Infrastructure\Capture\AuditableGuard;
use Yammi\AuditLog\Infrastructure\Capture\EloquentChangeRecorder;
use Yammi\AuditLog\Infrastructure\Label\NullLabelResolver;
use Yammi\AuditLog\Infrastructure\Persistence\Repository\EloquentAuditRecordRepository;
use Yammi\AuditLog\Infrastructure\Redaction\ConfigValueRedactor;
use Yammi\AuditLog\Infrastructure\Support\SystemClock;

final class AuditLogServiceProvider extends ServiceProvider
{
    private const CONFIG_PATH = __DIR__.'/../config/audit-log.php';

    private const MIGRATIONS_PATH = __DIR__.'/../database/migrations';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'audit-log');

        $this->app->bind(AuditRecordRepository::class, EloquentAuditRecordRepository::class);
        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(LabelResolver::class, NullLabelResolver::class);
        $this->app->bind(ActorResolver::class, SystemActorResolver::class);

        $this->app->bind(ValueRedactor::class, function (): ValueRedactor {
            $config = $this->config();

            return new ConfigValueRedactor(
                $this->stringList($config->get('audit-log.redaction.keys', [])),
                (string) $config->get('audit-log.redaction.placeholder', '[redacted]'),
            );
        });

        $this->app->bind(AuditableGuard::class, function (): AuditableGuard {
            return new AuditableGuard(
                $this->stringList($this->config()->get('audit-log.capture.exclude', [])),
            );
        });

        $this->app->bind(RecordChangePipeline::class, function (): RecordChangePipeline {
            return new RecordChangePipeline([
                $this->app->make(ComputeDiffStage::class),
                $this->app->make(ResolveActorStage::class),
                $this->app->make(ResolveLabelsStage::class),
            ]);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(self::MIGRATIONS_PATH);

        if ($this->app->runningInConsole()) {
            $this->publishes(
                [self::CONFIG_PATH => config_path('audit-log.php')],
                'audit-log-config',
            );

            $this->publishes(
                [self::MIGRATIONS_PATH => database_path('migrations')],
                'audit-log-migrations',
            );
        }

        if (! (bool) $this->config()->get('audit-log.enabled', true)) {
            return;
        }

        $events = $this->app->make(Dispatcher::class);

        foreach (['created', 'updated', 'deleted', 'restored'] as $verb) {
            $events->listen("eloquent.{$verb}: *", [EloquentChangeRecorder::class, 'handle']);
        }
    }

    private function config(): ConfigRepository
    {
        return $this->app->make(ConfigRepository::class);
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            if (is_string($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }
}
