<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\Repository;

use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;
use Yammi\AuditLog\Domain\Audit\Query\PagedRecords;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

interface AuditRecordRepository
{
    public function save(AuditRecord $record): void;

    /**
     * @return list<AuditRecord>
     */
    public function timelineFor(AuditableReference $auditable, int $limit = 50): array;

    public function paginate(AuditCriteria $criteria, int $page = 1, int $perPage = 25): PagedRecords;

    /**
     * @return list<AuditRecord>
     */
    public function findByCorrelation(string $correlationId): array;

    /**
     * @param  list<string>  $correlationIds
     * @return array<string, int>
     */
    public function countByCorrelations(array $correlationIds): array;

    /**
     * @return list<string>
     */
    public function distinctModels(): array;

    /**
     * @return list<string>
     */
    public function distinctActorTypes(): array;

    public function countNoise(): int;
}
