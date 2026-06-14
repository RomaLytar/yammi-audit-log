<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action\Read;

use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\Contract\Query\AuditLogQuery;
use Yammi\AuditLog\Application\DTO\Audit\RecordViewData;
use Yammi\AuditLog\Application\DTO\Audit\RelatedChangeData;
use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

/**
 * Builds the single-record page: the record's own history plus changes of
 * OTHER records connected to it — through shared correlation chains (the
 * cascades this record took part in) and through foreign-key references
 * (diffs of other models whose <model>_id points at this record).
 *
 * @internal
 */
final class BuildRecordViewAction
{
    public const HISTORY_LIMIT = 100;

    public const CHAIN_SAMPLE = 10;

    public const RELATED_LIMIT = 50;

    public function __construct(
        private readonly AuditLogQuery $query,
        private readonly Clock $clock,
    ) {}

    public function __invoke(AuditableReference $auditable): RecordViewData
    {
        $history = $this->query->historyFor($auditable, $this->clock->now(), self::HISTORY_LIMIT);

        $entries = TimelineEntryData::fromRecords(array_reverse($history));

        $referenceField = $this->referenceField($auditable->type);

        return new RecordViewData(
            auditableType: $auditable->type,
            auditableId: $auditable->id,
            referenceField: $referenceField,
            entries: $entries,
            related: $this->related($auditable, $history, $referenceField),
        );
    }

    /**
     * @param  list<AuditRecord>  $history
     * @return list<RelatedChangeData>
     */
    private function related(AuditableReference $auditable, array $history, string $referenceField): array
    {
        $related = [];
        $seen = [];

        foreach ($this->chainNeighbours($auditable, $history) as $record) {
            $key = $record->id() ?? spl_object_id($record);

            if (! isset($seen[$key]) && count($related) < self::RELATED_LIMIT) {
                $seen[$key] = true;
                $related[] = new RelatedChangeData(TimelineEntryData::fromRecord($record), RelatedChangeData::VIA_CHAIN);
            }
        }

        foreach ($this->referencing($auditable, $referenceField) as $record) {
            $key = $record->id() ?? spl_object_id($record);

            if (! isset($seen[$key]) && count($related) < self::RELATED_LIMIT) {
                $seen[$key] = true;
                $related[] = new RelatedChangeData(TimelineEntryData::fromRecord($record), RelatedChangeData::VIA_REFERENCE);
            }
        }

        return $related;
    }

    /**
     * @param  list<AuditRecord>  $history
     * @return list<AuditRecord>
     */
    private function chainNeighbours(AuditableReference $auditable, array $history): array
    {
        $correlationIds = [];

        foreach (array_reverse($history) as $record) {
            $correlationId = $record->correlationId();

            if ($correlationId !== null && ! in_array($correlationId, $correlationIds, true)) {
                $correlationIds[] = $correlationId;
            }

            if (count($correlationIds) >= self::CHAIN_SAMPLE) {
                break;
            }
        }

        $neighbours = [];

        foreach ($correlationIds as $correlationId) {
            foreach ($this->query->chain($correlationId) as $record) {
                if (! $record->auditable()->equals($auditable)) {
                    $neighbours[] = $record;
                }
            }
        }

        return $neighbours;
    }

    /**
     * @return list<AuditRecord>
     */
    private function referencing(AuditableReference $auditable, string $referenceField): array
    {
        $matches = [];

        foreach ($this->query->touchingField($referenceField) as $record) {
            if ($record->auditable()->equals($auditable)) {
                continue;
            }

            $fieldDiff = $record->diff()->field($referenceField);

            if ($fieldDiff === null) {
                continue;
            }

            if ($this->pointsAt($fieldDiff->old, $auditable->id) || $this->pointsAt($fieldDiff->new, $auditable->id)) {
                $matches[] = $record;
            }
        }

        return $matches;
    }

    /**
     * @param  scalar|array<array-key, mixed>|null  $value
     */
    private function pointsAt(mixed $value, string $id): bool
    {
        return is_scalar($value) && (string) $value === $id;
    }

    private function referenceField(string $type): string
    {
        $parts = explode('\\', $type);
        $short = end($parts) ?: $type;

        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $short));

        return $snake.'_id';
    }
}
