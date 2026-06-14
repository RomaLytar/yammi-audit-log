<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action\Read;

use DateTimeImmutable;
use DateTimeInterface;
use Yammi\AuditLog\Application\Contract\Query\AuditLogQuery;
use Yammi\AuditLog\Application\DTO\Audit\StateData;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

/**
 * Folds the recorded diffs of one record, oldest first, into the read-only
 * attribute state that record had at a given moment. A deletion keeps the
 * last-known values so the page can still show what was removed.
 *
 * @internal
 */
final class ReconstructStateAction
{
    public const HISTORY_LIMIT = 1000;

    public function __construct(
        private readonly AuditLogQuery $query,
    ) {}

    public function __invoke(AuditableReference $auditable, DateTimeImmutable $at): StateData
    {
        $records = $this->query->historyFor($auditable, $at, self::HISTORY_LIMIT);

        $attributes = [];
        $existed = false;
        $lastChangeAt = null;

        foreach ($records as $record) {
            $lastChangeAt = $record->occurredAt()->format(DateTimeInterface::ATOM);

            if ($record->event()->isDeletion()) {
                $existed = false;

                continue;
            }

            $existed = true;

            foreach ($record->diff()->fields() as $field => $fieldDiff) {
                $attributes[$field] = $fieldDiff->new;
            }
        }

        return new StateData(
            auditableType: $auditable->type,
            auditableId: $auditable->id,
            at: $at->format(DateTimeInterface::ATOM),
            existed: $existed,
            attributes: $attributes,
            appliedChanges: count($records),
            lastChangeAt: $lastChangeAt,
            truncated: count($records) >= self::HISTORY_LIMIT,
        );
    }
}
