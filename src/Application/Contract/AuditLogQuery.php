<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract;

use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;
use Yammi\AuditLog\Domain\Audit\Query\PagedRecords;

/**
 * Read-side port for the dashboard. Separate from the domain repository so the
 * write model stays free of UI-shaped queries (pagination, distinct filters,
 * counts, chain lookups).
 */
interface AuditLogQuery
{
    public function paginate(AuditCriteria $criteria, int $page = 1, int $perPage = 25): PagedRecords;

    /**
     * @return list<AuditRecord>
     */
    public function chain(string $correlationId): array;

    /**
     * @return list<string>
     */
    public function distinctModels(): array;

    /**
     * @return list<string>
     */
    public function distinctActorTypes(): array;

    public function countNoise(): int;

    /**
     * @param  list<string>  $correlationIds
     * @return array<string, int>
     */
    public function chainSizes(array $correlationIds): array;
}
