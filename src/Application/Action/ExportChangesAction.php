<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action;

use Yammi\AuditLog\Application\Contract\AuditLogQuery;
use Yammi\AuditLog\Application\DTO\AuditFilterData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Application\Service\CriteriaFactory;

/** @internal */
final class ExportChangesAction
{
    public const MAX_ROWS = 10000;

    public function __construct(
        private readonly AuditLogQuery $query,
        private readonly CriteriaFactory $criteria,
    ) {}

    /**
     * @return list<TimelineEntryData>
     */
    public function __invoke(AuditFilterData $filters): array
    {
        $entries = [];

        foreach ($this->query->all($this->criteria->fromFilters($filters), self::MAX_ROWS) as $record) {
            $entries[] = TimelineEntryData::fromRecord($record);
        }

        return $entries;
    }
}
