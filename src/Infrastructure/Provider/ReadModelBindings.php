<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Provider;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Yammi\AuditLog\Application\Contract\AuditDataTransferrer;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\Contract\Query\AuditLogQuery;
use Yammi\AuditLog\Application\Contract\Query\AuditStatsQuery;
use Yammi\AuditLog\Application\Contract\Resolver\LabelResolver;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Settings\Repository\GeneralSettingRepository;
use Yammi\AuditLog\Infrastructure\AuditLogManager;
use Yammi\AuditLog\Infrastructure\Label\ConventionLabelResolver;
use Yammi\AuditLog\Infrastructure\Persistence\Mapper\AuditRecordMapper;
use Yammi\AuditLog\Infrastructure\Persistence\Query\EloquentAuditLogQuery;
use Yammi\AuditLog\Infrastructure\Persistence\Query\EloquentAuditStatsQuery;
use Yammi\AuditLog\Infrastructure\Persistence\Repository\EloquentAuditRecordRepository;
use Yammi\AuditLog\Infrastructure\Persistence\Repository\QueuedAuditRecordRepository;
use Yammi\AuditLog\Infrastructure\Persistence\Transfer\EloquentAuditDataTransferrer;
use Yammi\AuditLog\Infrastructure\Reader\AuditReader;
use Yammi\AuditLog\Infrastructure\Settings\Persistence\Repository\EloquentGeneralSettingRepository;
use Yammi\AuditLog\Infrastructure\Support\SystemClock;
use Yammi\AuditLog\Infrastructure\Transfer\ConnectionStatusInspector;

/**
 * Read side: repositories, read-model queries, the reader/manager facade graph
 * and the settings/transfer support services.
 *
 * @internal
 */
final class ReadModelBindings extends BindingRegistrar
{
    public function register(): void
    {
        $this->app->singleton(AuditRecordRepository::class, function (): AuditRecordRepository {
            $config = $this->config();
            $inner = $this->app->make(EloquentAuditRecordRepository::class);

            if (! (bool) $config->get('audit-log.write.async', false)) {
                return $inner;
            }

            $queue = $config->get('audit-log.write.queue');

            return new QueuedAuditRecordRepository(
                $inner,
                $this->app->make(AuditRecordMapper::class),
                $this->app->make(BusDispatcher::class),
                is_string($queue) && $queue !== '' ? $queue : null,
            );
        });
        $this->app->singleton(AuditLogQuery::class, EloquentAuditLogQuery::class);
        $this->app->singleton(AuditStatsQuery::class, EloquentAuditStatsQuery::class);
        $this->app->singleton(Clock::class, SystemClock::class);

        $this->app->singleton(LabelResolver::class, function (): LabelResolver {
            return new ConventionLabelResolver(
                $this->classMap($this->config()->get('audit-log.labels.map', [])),
            );
        });
        $this->app->singleton(AuditReader::class);
        $this->app->singleton(AuditLogManager::class);

        $this->app->singleton(AuditDataTransferrer::class, function (): AuditDataTransferrer {
            return new EloquentAuditDataTransferrer(
                $this->app->make(ConnectionResolverInterface::class),
                $this->auditTable(),
            );
        });

        $this->app->singleton(GeneralSettingRepository::class, EloquentGeneralSettingRepository::class);

        $this->app->singleton(ConnectionStatusInspector::class, function (): ConnectionStatusInspector {
            return new ConnectionStatusInspector(
                $this->app->make(ConnectionResolverInterface::class),
                $this->config(),
                $this->auditTable(),
            );
        });
    }
}
