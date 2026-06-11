<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract;

use DateTimeImmutable;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;

/**
 * Read-side port for the statistics page. Separate from AuditLogQuery so the
 * list/dashboard port stays lean.
 */
interface AuditStatsQuery
{
    public function count(AuditCriteria $criteria): int;

    /**
     * @return array<string, int> event => count
     */
    public function eventBreakdown(AuditCriteria $criteria): array;

    /**
     * @return array<string, int> actor type => count
     */
    public function actorTypeBreakdown(AuditCriteria $criteria): array;

    /**
     * @return array<string, int> auditable type => count, largest first
     */
    public function modelBreakdown(AuditCriteria $criteria, int $limit = 10): array;

    /**
     * @return array<string, int> Y-m-d => count for every day in the window,
     *                            zero-filled, oldest first
     */
    public function dailyCounts(AuditCriteria $criteria, DateTimeImmutable $from, int $days): array;
}
