<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Throwable;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;
use Yammi\AuditLog\Infrastructure\AuditLogManager;

/**
 * Opt-in pivot auditing for host models. Eloquent fires no model events for
 * attach/detach/sync on a many-to-many relation, so these wrappers run the
 * relation operation and then record the before/after set of related keys
 * through the same pipeline as captured changes (redaction, attribution,
 * correlation). Without this, "the user's roles changed" leaves no trace.
 *
 * @mixin Model
 */
trait AuditsPivots
{
    /**
     * @param  array<array-key, mixed>  $attributes
     */
    public function auditAttach(string $relation, int|string|array|Model|Collection $ids, array $attributes = [], bool $touch = true): void
    {
        $this->recordPivotChange($relation, ChangeType::Attached, static function (BelongsToMany $pivot) use ($ids, $attributes, $touch): void {
            $pivot->attach($ids, $attributes, $touch);
        });
    }

    public function auditDetach(string $relation, int|string|array|Model|Collection|null $ids = null, bool $touch = true): int
    {
        $detached = 0;

        $this->recordPivotChange($relation, ChangeType::Detached, static function (BelongsToMany $pivot) use ($ids, $touch, &$detached): void {
            $detached = $pivot->detach($ids, $touch);
        });

        return $detached;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function auditSync(string $relation, int|string|array|Model|Collection $ids, bool $detaching = true): array
    {
        $result = [];

        $this->recordPivotChange($relation, ChangeType::Synced, static function (BelongsToMany $pivot) use ($ids, $detaching, &$result): void {
            $result = $pivot->sync($ids, $detaching);
        });

        return $result;
    }

    private function recordPivotChange(string $relation, ChangeType $event, Closure $operation): void
    {
        $pivot = $this->{$relation}();

        if (! $pivot instanceof BelongsToMany) {
            throw InvalidAuditData::notManyToMany($relation);
        }

        $before = $this->relatedKeySnapshot($pivot);

        $operation($pivot);

        $after = $this->relatedKeySnapshot($pivot);

        if ($before === $after) {
            return;
        }

        try {
            app(AuditLogManager::class)->record(
                $this->getMorphClass(),
                (string) $this->getKey(),
                $event,
                [$relation => $before],
                [$relation => $after],
            );
        } catch (Throwable $exception) {
            app(LoggerInterface::class)->error(
                'Audit pivot capture failed: '.$exception->getMessage(),
                ['exception' => $exception],
            );
        }
    }

    /**
     * @return list<string>
     */
    private function relatedKeySnapshot(BelongsToMany $pivot): array
    {
        $related = $pivot->getRelated();

        return array_values(
            $pivot->get([$related->getQualifiedKeyName()])
                ->pluck($related->getKeyName())
                ->map(static fn (mixed $key): string => (string) $key)
                ->sort()
                ->all()
        );
    }
}
