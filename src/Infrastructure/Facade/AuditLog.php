<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Facade;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Yammi\AuditLog\Application\DTO\Anomaly\AnomalyData;
use Yammi\AuditLog\Application\DTO\Audit\ChainData;
use Yammi\AuditLog\Application\DTO\Audit\ChangeListData;
use Yammi\AuditLog\Application\DTO\Audit\RecordViewData;
use Yammi\AuditLog\Application\DTO\Audit\StateData;
use Yammi\AuditLog\Application\DTO\Audit\SubjectReportData;
use Yammi\AuditLog\Application\DTO\Audit\TimelineData;
use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;
use Yammi\AuditLog\Application\DTO\Stats\StatsData;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Infrastructure\AuditLogManager;

/**
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
    protected static function getFacadeAccessor(): string
    {
        return AuditLogManager::class;
    }
}
