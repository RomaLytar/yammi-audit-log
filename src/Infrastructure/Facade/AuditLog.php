<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Facade;

use Closure;
use DateTimeImmutable;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use RuntimeException;
use Yammi\AuditLog\Application\Action\Record\RecordChangeAction;
use Yammi\AuditLog\Application\DTO\Anomaly\AnomalyData;
use Yammi\AuditLog\Application\DTO\Audit\ChainData;
use Yammi\AuditLog\Application\DTO\Audit\ChangeListData;
use Yammi\AuditLog\Application\DTO\Audit\RecordViewData;
use Yammi\AuditLog\Application\DTO\Audit\StateData;
use Yammi\AuditLog\Application\DTO\Audit\SubjectReportData;
use Yammi\AuditLog\Application\DTO\Audit\TimelineData;
use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;
use Yammi\AuditLog\Application\DTO\Stats\StatsData;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Infrastructure\AuditLogManager;
use Yammi\AuditLog\Infrastructure\Capture\EloquentChangeRecorder;
use Yammi\AuditLog\Infrastructure\Policy\AuditPolicy;
use Yammi\AuditLog\Infrastructure\Query\AuditQueryBuilder;
use Yammi\AuditLog\Infrastructure\Recorder\ManualChangeRecorder;
use Yammi\AuditLog\Infrastructure\Testing\AuditLogFake;

/**
 * @method static AuditPolicy policy(string $model)
 * @method static AuditQueryBuilder query()
 * @method static TimelineData for(Model|string $auditable, int|string|null $id = null, int $limit = 50)
 * @method static StateData stateAt(Model|string $auditable, int|string|null $id = null, DateTimeImmutable|string|null $at = null)
 * @method static ChangeListData changes(array $filters = [])
 * @method static ChangeListData noise(array $filters = [])
 * @method static ChainData|null chain(string $correlationId)
 * @method static StatsData stats(array $filters = [])
 * @method static list<AnomalyData> anomalies(int|null $windowMinutes = null)
 * @method static SubjectReportData subjectReport(Model|string $auditable, int|string|null $id = null)
 * @method static RecordViewData recordView(Model|string $auditable, int|string|null $id = null)
 * @method static TimelineEntryData|null record(Model|string $auditable, int|string|null $id, ChangeType|string $event, array $before = [], array $after = [])
 * @method static TimelineEntryData|null recordAccess(Model|string $auditable, int|string|null $id = null)
 * @method static mixed withReason(string $reason, callable $callback)
 * @method static string activityUrl(Model|string $auditable, int|string|null $id = null, int $minutes = 60)
 *
 * @see AuditLogManager
 */
final class AuditLog extends Facade
{
    /**
     * Swap the real audit repository for an in-memory fake, so a test can assert
     * what its code recorded without writing to the database. Both automatic
     * (Eloquent) and manual (AuditLog::record) changes flow into the fake.
     */
    public static function fake(): AuditLogFake
    {
        $container = Container::getInstance();
        $fake = new AuditLogFake;

        $container->instance(AuditRecordRepository::class, $fake);

        foreach ([
            RecordChangeAction::class,
            EloquentChangeRecorder::class,
            ManualChangeRecorder::class,
            AuditLogManager::class,
        ] as $abstract) {
            $container->forgetInstance($abstract);
        }

        self::clearResolvedInstance(AuditLogManager::class);

        return $fake;
    }

    /**
     * @param  (Closure(AuditRecord): bool)|null  $matcher
     */
    public static function assertRecorded(string $type, int|string|null $id = null, ChangeType|string|null $event = null, ?Closure $matcher = null): void
    {
        self::activeFake()->assertRecorded($type, $id, $event, $matcher);
    }

    /**
     * @param  (Closure(AuditRecord): bool)|null  $matcher
     */
    public static function assertNotRecorded(string $type, int|string|null $id = null, ChangeType|string|null $event = null, ?Closure $matcher = null): void
    {
        self::activeFake()->assertNotRecorded($type, $id, $event, $matcher);
    }

    public static function assertNothingRecorded(): void
    {
        self::activeFake()->assertNothingRecorded();
    }

    public static function assertRecordedCount(int $count): void
    {
        self::activeFake()->assertRecordedCount($count);
    }

    private static function activeFake(): AuditLogFake
    {
        $repository = Container::getInstance()->make(AuditRecordRepository::class);

        if (! $repository instanceof AuditLogFake) {
            throw new RuntimeException('Call AuditLog::fake() before asserting on recorded audit changes.');
        }

        return $repository;
    }

    protected static function getFacadeAccessor(): string
    {
        return AuditLogManager::class;
    }
}
