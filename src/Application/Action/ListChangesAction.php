<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action;

use Yammi\AuditLog\Application\Contract\AuditLogQuery;
use Yammi\AuditLog\Application\DTO\AuditFilterData;
use Yammi\AuditLog\Application\DTO\ChangeListData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Application\Service\CriteriaFactory;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;

/** @internal */
final class ListChangesAction
{
    private const PER_PAGE = 25;

    public function __construct(
        private readonly AuditLogQuery $query,
        private readonly CriteriaFactory $criteria,
    ) {}

    public function __invoke(AuditFilterData $filters, ?bool $onlyNoise = null): ChangeListData
    {
        $criteria = $this->criteria->fromFilters($filters, $onlyNoise);

        $paged = $this->query->paginate($criteria, max(1, $filters->page), self::PER_PAGE);

        $entries = [];
        $correlationIds = [];

        foreach ($paged->records as $record) {
            $entry = TimelineEntryData::fromRecord($record);
            $entries[] = $entry;

            if ($entry->correlationId !== null) {
                $correlationIds[$entry->correlationId] = true;
            }
        }

        return new ChangeListData(
            entries: $entries,
            total: $paged->total,
            page: $paged->page,
            perPage: $paged->perPage,
            lastPage: $paged->lastPage(),
            models: $this->query->distinctModels(),
            actorTypes: $this->query->distinctActorTypes(),
            events: ChangeType::values(),
            filters: $filters,
            correlationSizes: $this->query->chainSizes(array_keys($correlationIds)),
        );
    }
}
