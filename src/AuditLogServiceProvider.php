<?php

declare(strict_types=1);

namespace Yammi\AuditLog;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\ServiceProvider;
use Yammi\AuditLog\Application\Action\Record\RecordChangeAction;
use Yammi\AuditLog\Application\Contract\Resolver\TenantResolver;
use Yammi\AuditLog\Application\Playground\MethodCatalog;
use Yammi\AuditLog\Application\Service\CriteriaFactory;
use Yammi\AuditLog\Application\Service\FilterParser;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Infrastructure\Actor\ActorSerializer;
use Yammi\AuditLog\Infrastructure\Capture\CaptureRegistrar;
use Yammi\AuditLog\Infrastructure\Capture\ChangeDataFactory;
use Yammi\AuditLog\Infrastructure\Capture\EloquentChangeRecorder;
use Yammi\AuditLog\Infrastructure\Console\ArchiveAuditLogCommand;
use Yammi\AuditLog\Infrastructure\Console\DetectAnomaliesCommand;
use Yammi\AuditLog\Infrastructure\Console\GenerateDigestCommand;
use Yammi\AuditLog\Infrastructure\Console\PruneAuditLogCommand;
use Yammi\AuditLog\Infrastructure\Console\SubjectReportCommand;
use Yammi\AuditLog\Infrastructure\Console\ToggleUiCommand;
use Yammi\AuditLog\Infrastructure\Console\TransferAuditDataCommand;
use Yammi\AuditLog\Infrastructure\Console\VerifyIntegrityCommand;
use Yammi\AuditLog\Infrastructure\Context\ContextRegistrar;
use Yammi\AuditLog\Infrastructure\Http\CorrelationMiddlewareRegistrar;
use Yammi\AuditLog\Infrastructure\Http\FilterFactory;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Infrastructure\Persistence\Mapper\AuditRecordMapper;
use Yammi\AuditLog\Infrastructure\Provider\AlertingBindings;
use Yammi\AuditLog\Infrastructure\Provider\CaptureBindings;
use Yammi\AuditLog\Infrastructure\Provider\HttpRegistrar;
use Yammi\AuditLog\Infrastructure\Provider\IntegrityBindings;
use Yammi\AuditLog\Infrastructure\Provider\ReadModelBindings;
use Yammi\AuditLog\Infrastructure\Provider\ScheduleRegistrar;
use Yammi\AuditLog\Infrastructure\Provider\TenancyBindings;
use Yammi\AuditLog\Infrastructure\Settings\StoredSettingsApplier;

final class AuditLogServiceProvider extends ServiceProvider
{
    private const CONFIG_PATH = __DIR__.'/../config/audit-log.php';

    private const MIGRATIONS_PATH = __DIR__.'/../database/migrations';

    private const VIEWS_PATH = __DIR__.'/../resources/views';

    private const ROUTES_PATH = __DIR__.'/../routes/web.php';

    private const API_ROUTES_PATH = __DIR__.'/../routes/api.php';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'audit-log');

        (new ReadModelBindings($this->app))->register();
        (new CaptureBindings($this->app))->register();
        (new AlertingBindings($this->app))->register();
        (new IntegrityBindings($this->app))->register();
        (new TenancyBindings($this->app))->register();

        foreach ([
            EloquentChangeRecorder::class,
            RecordChangeAction::class,
            ChangeDataFactory::class,
            AuditRecordMapper::class,
            ActorSerializer::class,
            CriteriaFactory::class,
            FilterParser::class,
            FilterFactory::class,
            SettingRegistry::class,
            MethodCatalog::class,
        ] as $stateless) {
            $this->app->singleton($stateless);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(self::MIGRATIONS_PATH);
        $this->loadViewsFrom(self::VIEWS_PATH, 'audit-log');

        if ($this->app->runningInConsole()) {
            $this->commands([PruneAuditLogCommand::class, TransferAuditDataCommand::class, ToggleUiCommand::class, VerifyIntegrityCommand::class, ArchiveAuditLogCommand::class, SubjectReportCommand::class, DetectAnomaliesCommand::class, GenerateDigestCommand::class]);

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

        $this->app->make(StoredSettingsApplier::class)->apply();
        $this->registerTenantScope();

        (new HttpRegistrar($this->app, self::ROUTES_PATH, self::API_ROUTES_PATH))->register($config);
        (new ScheduleRegistrar(
            fn (string $abstract, callable $callback) => $this->callAfterResolving($abstract, $callback),
        ))->register($config);

        if (! (bool) $config->get('audit-log.enabled', true)) {
            return;
        }

        $this->app->make(CaptureRegistrar::class)->register();
        $this->app->make(ContextRegistrar::class)->register();
        $this->app->make(CorrelationMiddlewareRegistrar::class)->register();
    }

    private function registerTenantScope(): void
    {
        AuditRecordModel::addGlobalScope(
            'audit-log-tenant',
            /** @param  EloquentBuilder<AuditRecordModel>  $query */
            function (EloquentBuilder $query): void {
                $tenant = $this->app->make(TenantResolver::class)->resolve();

                if ($tenant !== null && $tenant !== '') {
                    $query->where($query->qualifyColumn('tenant_id'), $tenant);
                }
            },
        );
    }

    private function config(): ConfigRepository
    {
        return $this->app->make(ConfigRepository::class);
    }
}
