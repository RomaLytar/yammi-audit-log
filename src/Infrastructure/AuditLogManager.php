<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure;

use DateTimeImmutable;
use Exception;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Application\Action\BuildChainAction;
use Yammi\AuditLog\Application\Action\BuildStatsAction;
use Yammi\AuditLog\Application\Action\ListChangesAction;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\DTO\AnomalyData;
use Yammi\AuditLog\Application\DTO\ChainData;
use Yammi\AuditLog\Application\DTO\ChangeListData;
use Yammi\AuditLog\Application\DTO\RecordViewData;
use Yammi\AuditLog\Application\DTO\StateData;
use Yammi\AuditLog\Application\DTO\StatsData;
use Yammi\AuditLog\Application\DTO\SubjectReportData;
use Yammi\AuditLog\Application\DTO\TimelineData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Application\Service\FilterParser;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;
use Yammi\AuditLog\Infrastructure\Anomaly\AnomalyScanner;
use Yammi\AuditLog\Infrastructure\Reader\AuditReader;
use Yammi\AuditLog\Infrastructure\Recorder\ManualChangeRecorder;

/**
 * The facade root: everything the bundled dashboard shows, exposed as plain
 * DTOs so a host can embed the audit data in its own admin instead.
 *
 * @internal
 */
final class AuditLogManager
{
    public function __construct(
        private readonly AuditReader $reader,
        private readonly ManualChangeRecorder $recorder,
        private readonly ListChangesAction $listChanges,
        private readonly BuildChainAction $buildChain,
        private readonly BuildStatsAction $buildStats,
        private readonly FilterParser $filters,
        private readonly ConfigRepository $config,
        private readonly Clock $clock,
        private readonly AnomalyScanner $anomalyScanner,
    ) {}

    public function for(Model|string $auditable, int|string|null $id = null, int $limit = 50): TimelineData
    {
        return $this->reader->for($auditable, $id, $limit);
    }

    /**
     * The read-only state one record had at a moment, folded from its diffs.
     * A date-only string means the end of that day; null means now.
     */
    public function stateAt(
        Model|string $auditable,
        int|string|null $id = null,
        DateTimeImmutable|string|null $at = null,
    ): StateData {
        return $this->reader->stateAt($auditable, $id, $this->resolveMoment($at));
    }

    /**
     * The GDPR subject access report as data: every change to the record
     * plus every change the record made as a user actor.
     */
    public function subjectReport(Model|string $auditable, int|string|null $id = null): SubjectReportData
    {
        return $this->reader->subjectReport($auditable, $id);
    }

    /**
     * The single-record page as data: the record's own history plus changes
     * of other records connected through correlation chains and foreign-key
     * references.
     */
    public function recordView(Model|string $auditable, int|string|null $id = null): RecordViewData
    {
        return $this->reader->recordView($auditable, $id);
    }

    /**
     * The dashboard list: filtered, paginated changes plus the filter options.
     * Filters: model, event, actor_type, actor, id, from, to, search, page.
     *
     * @param  array<string, mixed>  $filters
     */
    public function changes(array $filters = []): ChangeListData
    {
        return ($this->listChanges)($this->filters->fromArray($filters));
    }

    /**
     * Only the no-op writes (double saves) — the Noise page as data.
     *
     * @param  array<string, mixed>  $filters
     */
    public function noise(array $filters = []): ChangeListData
    {
        return ($this->listChanges)($this->filters->fromArray($filters), onlyNoise: true);
    }

    /**
     * The full cross-model change chain behind one correlation id.
     */
    public function chain(string $correlationId): ?ChainData
    {
        return ($this->buildChain)($correlationId);
    }

    /**
     * The statistics page as data: volume, breakdowns and daily activity,
     * narrowed by the same filters as changes().
     *
     * @param  array<string, mixed>  $filters
     */
    public function stats(array $filters = []): StatsData
    {
        $retention = $this->config->get('audit-log.retention.days', 0);

        return ($this->buildStats)(
            $this->filters->fromArray($filters),
            is_numeric($retention) ? (int) $retention : 0,
        );
    }

    /**
     * The anomaly scan as data: change bursts, mass deletions and off-hours
     * user activity inside the look-back window. Null means the configured
     * anomalies.window_minutes.
     *
     * @return list<AnomalyData>
     */
    public function anomalies(?int $windowMinutes = null): array
    {
        $configured = $this->config->get('audit-log.anomalies.window_minutes', 60);

        $window = $windowMinutes ?? (is_numeric($configured) ? (int) $configured : 60);

        return $this->anomalyScanner->scan(max(1, $window));
    }

    /**
     * @param  array<string, scalar|array<array-key, mixed>|null>  $before
     * @param  array<string, scalar|array<array-key, mixed>|null>  $after
     */
    public function record(
        Model|string $auditable,
        int|string|null $id,
        ChangeType|string $event,
        array $before = [],
        array $after = [],
    ): ?TimelineEntryData {
        return $this->recorder->record($auditable, $id, $event, $before, $after);
    }

    /**
     * Records that a record was read — "who viewed this", not just who changed
     * it. An access has no diff; the actor and request metadata are attributed
     * through the same pipeline as any captured change.
     */
    public function recordAccess(Model|string $auditable, int|string|null $id = null): ?TimelineEntryData
    {
        return $this->recorder->recordAccess($auditable, $id);
    }

    private function resolveMoment(DateTimeImmutable|string|null $at): DateTimeImmutable
    {
        if ($at instanceof DateTimeImmutable) {
            return $at;
        }

        $raw = trim((string) $at);

        if ($raw === '') {
            return $this->clock->now();
        }

        try {
            $moment = new DateTimeImmutable($raw);
        } catch (Exception) {
            throw InvalidAuditData::invalidDate($raw);
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1
            ? $moment->setTime(23, 59, 59)
            : $moment;
    }
}
