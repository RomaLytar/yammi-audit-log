<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Application\Action\BuildChainAction;
use Yammi\AuditLog\Application\Action\BuildStatsAction;
use Yammi\AuditLog\Application\Action\ListChangesAction;
use Yammi\AuditLog\Application\DTO\ChainData;
use Yammi\AuditLog\Application\DTO\ChangeListData;
use Yammi\AuditLog\Application\DTO\StatsData;
use Yammi\AuditLog\Application\DTO\TimelineData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Application\Service\FilterParser;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
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
    ) {}

    public function for(Model|string $auditable, int|string|null $id = null, int $limit = 50): TimelineData
    {
        return $this->reader->for($auditable, $id, $limit);
    }

    /**
     * The dashboard list: filtered, paginated changes plus the filter options.
     * Filters: model, event, actor_type, actor, from, to, search, page.
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
}
