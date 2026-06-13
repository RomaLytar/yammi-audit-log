<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action\Read;

use Yammi\AuditLog\Application\Contract\Query\AuditLogQuery;
use Yammi\AuditLog\Application\DTO\Audit\AuditFilterData;
use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;
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
        return TimelineEntryData::fromRecords(
            $this->query->all($this->criteria->fromFilters($filters), self::MAX_ROWS),
        );
    }
}
