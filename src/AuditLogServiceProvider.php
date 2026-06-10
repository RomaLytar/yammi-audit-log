<?php

declare(strict_types=1);

namespace Yammi\AuditLog;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Yammi\AuditLog\Application\Contract\ActorResolver;
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
use Yammi\AuditLog\Infrastructure\Actor\ActorSerializer;
use Yammi\AuditLog\Infrastructure\Actor\Provider\AuthenticatedUserProvider;
use Yammi\AuditLog\Infrastructure\Actor\Provider\ConsoleActorProvider;
use Yammi\AuditLog\Infrastructure\Actor\Provider\QueuedJobActorProvider;
use Yammi\AuditLog\Infrastructure\Capture\AuditableGuard;
use Yammi\AuditLog\Infrastructure\Capture\EloquentChangeRecorder;
use Yammi\AuditLog\Infrastructure\Correlation\ContextCorrelationResolver;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;
use Yammi\AuditLog\Infrastructure\Http\Middleware\StartAuditCorrelation;
use Yammi\AuditLog\Infrastructure\Label\NullLabelResolver;
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
        }

        if (! (bool) $config->get('audit-log.enabled', true)) {
            return;
        }

        $events = $this->app->make(Dispatcher::class);

        foreach (['created', 'updated', 'deleted', 'restored'] as $verb) {
            $events->listen("eloquent.{$verb}: *", [EloquentChangeRecorder::class, 'handle']);
        }

        $this->trackActorContext($events);
        $this->registerCorrelationMiddleware();
    }

    private function registerCorrelationMiddleware(): void
    {
        $this->app->booted(function (): void {
            $kernel = $this->app->make(HttpKernelContract::class);

            if ($kernel instanceof HttpKernel) {
                $kernel->pushMiddleware(StartAuditCorrelation::class);
            }
        });
    }

    private function registerRoutes(ConfigRepository $config): void
    {
        $path = $config->get('audit-log.ui.path', 'audit-log');
        $middleware = $config->get('audit-log.ui.middleware', ['web']);

        $this->app->make(Router::class)->group([
            'prefix' => is_string($path) ? $path : 'audit-log',
            'middleware' => is_array($middleware) ? $middleware : ['web'],
        ], function (): void {
            $this->loadRoutesFrom(self::ROUTES_PATH);
        });
    }

    private function trackActorContext(Dispatcher $events): void
    {
        $context = $this->app->make(ActorContext::class);
        $correlation = $this->app->make(CorrelationContext::class);
        $serializer = $this->app->make(ActorSerializer::class);

        $events->listen(JobProcessing::class, function (JobProcessing $event) use ($context, $correlation, $serializer): void {
            $payload = $event->job->payload();

            $origin = isset($payload['audit_origin']) && is_array($payload['audit_origin'])
                ? $serializer->fromArray($payload['audit_origin'])
                : $this->app->make(ActorResolver::class)->resolve();

            $context->enterJob($event->job->resolveName(), $origin);

            $correlationId = isset($payload['audit_correlation']) && is_string($payload['audit_correlation'])
                ? $payload['audit_correlation']
                : ($correlation->current() ?? (string) Str::uuid());

            $correlation->push($correlationId);
        });

        $events->listen([JobProcessed::class, JobFailed::class], static function () use ($context, $correlation): void {
            $context->leaveJob();
            $correlation->pop();
        });

        $events->listen(CommandStarting::class, static function (CommandStarting $event) use ($context, $correlation): void {
            $context->enterCommand($event->command);
            $correlation->push((string) Str::uuid());
        });

        $events->listen(CommandFinished::class, static function () use ($correlation): void {
            $correlation->pop();
        });

        Queue::createPayloadUsing(function ($connection, $queue, $payload) use ($context, $correlation, $serializer): array {
            $extra = ['audit_correlation' => $correlation->current() ?? (string) Str::uuid()];

            $origin = $context->currentOrigin() ?? $this->app->make(ActorResolver::class)->resolve();

            if (! $origin->isAnonymous()) {
                $extra['audit_origin'] = $serializer->toArray($origin);
            }

            return $extra;
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
