<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Query;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

/**
 * Translates domain criteria into query constraints — shared by the list and
 * the statistics queries so every screen interprets filters identically.
 *
 * @internal
 */
final class AuditCriteriaApplier
{
    /**
     * @param  Builder<AuditRecordModel>  $query
     */
    public function apply(Builder $query, AuditCriteria $criteria): void
    {
        $query->where(array_filter([
            'auditable_type' => $criteria->auditableType,
            'auditable_id' => $criteria->auditableId,
            'event' => $criteria->event?->value,
            'actor_type' => $criteria->actorType?->value,
        ], static fn (?string $value): bool => $value !== null));

        if ($criteria->actorLabel !== null) {
            $query->whereRaw("actor_label like ? escape '!'", ['%'.$this->escapeLike($criteria->actorLabel).'%']);
        }

        if ($criteria->onlyNoise !== null) {
            $query->where('is_noise', $criteria->onlyNoise);
        }

        if ($criteria->search !== null) {
            $term = '%'.$this->escapeLike($criteria->search).'%';
            $changesAsText = $this->changesAsText($query);

            $query->where(function (Builder $nested) use ($term, $changesAsText, $criteria): void {
                $nested->whereRaw("{$changesAsText} like ? escape '!'", [$term])
                    ->orWhere('auditable_id', $criteria->search);
            });
        }

        if ($criteria->from !== null) {
            $query->where('occurred_at', '>=', $criteria->from->setTime(0, 0)->format('Y-m-d H:i:s'));
        }

        if ($criteria->to !== null) {
            $query->where('occurred_at', '<', $criteria->to->setTime(0, 0)->modify('+1 day')->format('Y-m-d H:i:s'));
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    /**
     * The changes column is JSON; LIKE needs a text expression per driver.
     *
     * @param  Builder<AuditRecordModel>  $query
     */
    private function changesAsText(Builder $query): string
    {
        $connection = $query->getConnection();
        $driver = $connection instanceof Connection ? $connection->getDriverName() : '';

        return match ($driver) {
            'pgsql' => 'changes::text',
            'mysql', 'mariadb' => 'cast(changes as char)',
            default => 'cast(changes as text)',
        };
    }
}
