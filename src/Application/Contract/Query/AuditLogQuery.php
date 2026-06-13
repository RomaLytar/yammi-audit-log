<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract\Query;

use DateTimeImmutable;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;
use Yammi\AuditLog\Domain\Audit\Query\PagedRecords;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

/**
 * Read-side port for the dashboard. Separate from the domain repository so the
 * write model stays free of UI-shaped queries (pagination, distinct filters,
 * counts, chain lookups).
 */
interface AuditLogQuery
{
    public function paginate(AuditCriteria $criteria, int $page = 1, int $perPage = 25): PagedRecords;

    /**
     * Newest first, capped at $limit.
     *
     * @return list<AuditRecord>
     */
    public function all(AuditCriteria $criteria, int $limit): array;

    /**
     * @return list<AuditRecord>
     */
    public function chain(string $correlationId): array;

    /**
     * The full history of one record up to a moment, oldest first, capped
     * at $limit.
     *
     * @return list<AuditRecord>
     */
    public function historyFor(AuditableReference $auditable, DateTimeImmutable $until, int $limit = 1000): array;

    /**
     * Every change performed BY one actor (exact identifier match), oldest
     * first, capped at $limit.
     *
     * @return list<AuditRecord>
     */
    public function byActor(ActorType $type, string $identifier, int $limit = 10000): array;

    /**
     * Changes of OTHER records whose diff touched the given field — used to
     * find foreign-key references back to one record. Newest first, capped;
     * callers match the exact value on the returned diffs.
     *
     * @return list<AuditRecord>
     */
    public function touchingField(string $field, int $limit = 500): array;

    /**
     * @return list<string>
     */
    public function distinctModels(): array;

    /**
     * @return list<string>
     */
    public function distinctActorTypes(): array;

    public function countNoise(): int;

    public function countAll(): int;

    public function countSince(DateTimeImmutable $cutoff): int;

    /**
     * @param  list<string>  $correlationIds
     * @return array<string, int>
     */
    public function chainSizes(array $correlationIds): array;
}
