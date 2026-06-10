<?php

declare(strict_types=1);

namespace Yammi\AuditLog;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Throwable;
use Yammi\AuditLog\Application\Contract\ActorResolver;
use Yammi\AuditLog\Application\Contract\AuditLogQuery;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\Contract\CorrelationResolver;
use Yammi\AuditLog\Application\Contract\LabelResolver;
use Yammi\AuditLog\Application\Contract\ValueRedactor;
use Yammi\AuditLog\Application\Pipeline\RecordChangePipeline;
use Yammi\AuditLog\Application\Pipeline\Stage\ComputeDiffStage;
use Yammi\AuditLog\Application\Pipeline\Stage\ResolveActorStage;
use Yammi\AuditLog\Application\Pipeline\Stage\ResolveLabelsStage;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Infrastructure\Actor\ActorContext;
use Yammi\AuditLog\Infrastructure\Actor\ActorResolverChain;
use Yammi\AuditLog\Infrastructure\Actor\Provider\AuthenticatedUserProvider;
use Yammi\AuditLog\Infrastructure\Actor\Provider\ConsoleActorProvider;
use Yammi\AuditLog\Infrastructure\Actor\Provider\QueuedJobActorProvider;
use Yammi\AuditLog\Infrastructure\Capture\AuditableGuard;
use Yammi\AuditLog\Infrastructure\Capture\CaptureRegistrar;
use Yammi\AuditLog\Infrastructure\Console\PruneAuditLogCommand;
use Yammi\AuditLog\Infrastructure\Context\ContextRegistrar;
use Yammi\AuditLog\Infrastructure\Correlation\ContextCorrelationResolver;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;
use Yammi\AuditLog\Infrastructure\Http\CorrelationMiddlewareRegistrar;
use Yammi\AuditLog\Infrastructure\Label\NullLabelResolver;
use Yammi\AuditLog\Infrastructure\Persistence\Query\EloquentAuditLogQuery;
use Yammi\AuditLog\Infrastructure\Persistence\Repository\EloquentAuditRecordRepository;
use Yammi\AuditLog\Infrastructure\Reader\AuditReader;
use Yammi\AuditLog\Infrastructure\Redaction\ConfigValueRedactor;
use Yammi\AuditLog\Infrastructure\Support\SystemClock;

final class AuditLogServiceProvider extends ServiceProvider
{
    private const CONFIG_PATH = __DIR__.'/../config/audit-log.php';

    private const MIGRATIONS_PATH = __DIR__.'/../database/migrations';

    private const VIEWS_PATH = __DIR__.'/../resources/views';

    private const ROUTES_PATH = __DIR__.'/../routes/web.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'audit-log');

        $this->app->bind(AuditRecordRepository::class, EloquentAuditRecordRepository::class);
        $this->app->bind(AuditLogQuery::class, EloquentAuditLogQuery::class);
        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(LabelResolver::class, NullLabelResolver::class);
        $this->app->singleton(AuditReader::class);

        $this->app->singleton(ActorContext::class);
        $this->app->singleton(CorrelationContext::class);
        $this->app->bind(CorrelationResolver::class, ContextCorrelationResolver::class);

        $this->app->bind(AuthenticatedUserProvider::class, function (): AuthenticatedUserProvider {
            return new AuthenticatedUserProvider(
                $this->app->make(AuthFactory::class),
                $this->stringList($this->config()->get('audit-log.actor.guards', [])),
            );
        });

        $this->app->bind(ActorResolver::class, function (): ActorResolver {
            return new ActorResolverChain([
                $this->app->make(QueuedJobActorProvider::class),
                $this->app->make(ConsoleActorProvider::class),
                $this->app->make(AuthenticatedUserProvider::class),
            ], $this->app->make(ActorContext::class));
        });

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

        $this->app->bind(ComputeDiffStage::class, function (): ComputeDiffStage {
            return new ComputeDiffStage(
                $this->app->make(ValueRedactor::class),
                $this->stringList($this->config()->get('audit-log.capture.ignore_attributes', [])),
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
        $this->loadViewsFrom(self::VIEWS_PATH, 'audit-log');

        if ($this->app->runningInConsole()) {
            $this->publishes(
                [self::CONFIG_PATH => config_path('audit-log.php')],
                'audit-log-config',
            );

            $this->publishes(
                [self::MIGRATIONS_PATH => database_path('migrations')],
                'audit-log-migrations',
            );

            $this->publishes(
                [self::VIEWS_PATH => resource_path('views/vendor/audit-log')],
                'audit-log-views',
            );
        }

        $config = $this->config();

        if ((bool) $config->get('audit-log.ui.enabled', true)) {
            $this->registerRoutes($config);
            $this->registerNavComposer();
        }

        $this->registerRetention($config);

        if (! (bool) $config->get('audit-log.enabled', true)) {
            return;
        }

        $this->app->make(CaptureRegistrar::class)->register();
        $this->app->make(ContextRegistrar::class)->register();
        $this->app->make(CorrelationMiddlewareRegistrar::class)->register();
    }

    private function registerRetention(ConfigRepository $config): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PruneAuditLogCommand::class]);
        }

        $days = (int) $config->get('audit-log.retention.days', 0);

        if ($days <= 0 || ! (bool) $config->get('audit-log.retention.schedule.enabled', true)) {
            return;
        }

        $this->app->booted(function () use ($config): void {
            $this->app->make(Schedule::class)
                ->command(PruneAuditLogCommand::class)
                ->cron((string) $config->get('audit-log.retention.schedule.cron', '0 3 * * *'))
                ->name('audit-log:prune')
                ->withoutOverlapping();
        });
    }

    private function registerNavComposer(): void
    {
        $this->app->make(ViewFactory::class)->composer(
            'audit-log::layouts.app',
            function (View $view): void {
                try {
                    $view->with('auditNoiseCount', $this->app->make(AuditLogQuery::class)->countNoise());
                } catch (Throwable) {
                    $view->with('auditNoiseCount', 0);
                }
            },
        );
    }

    private function registerRoutes(ConfigRepository $config): void
    {
        $path = $config->get('audit-log.ui.path', 'audit-log');
        $configured = $config->get('audit-log.ui.middleware', ['web']);
        $middleware = is_array($configured) ? array_values($configured) : ['web'];

        $throttle = $config->get('audit-log.ui.throttle');
        if (is_string($throttle) && $throttle !== '') {
            $middleware[] = 'throttle:'.$throttle;
        }

        $gate = $config->get('audit-log.ui.gate');
        if (is_string($gate) && $gate !== '') {
            $middleware[] = 'can:'.$gate;
        }

        $this->app->make(Router::class)->group([
            'prefix' => is_string($path) ? $path : 'audit-log',
            'middleware' => $middleware,
        ], function (): void {
            $this->loadRoutesFrom(self::ROUTES_PATH);
        });
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
