<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Mapper;

use DateTimeImmutable;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\FieldDiff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

final class AuditRecordMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toAttributes(AuditRecord $record): array
    {
        $occurredAt = $record->occurredAt()->format('Y-m-d H:i:s');

        return [
            'auditable_type' => $record->auditable()->type,
            'auditable_id' => $record->auditable()->id,
            'event' => $record->event()->value,
            'changes' => $record->diff()->toArray(),
            'actor_type' => $record->actor()->type->value,
            'actor_id' => $record->actor()->identifier,
            'actor_label' => $record->actor()->label,
            'origin_type' => $record->origin()?->type->value,
            'origin_id' => $record->origin()?->identifier,
            'origin_label' => $record->origin()?->label,
            'labels' => $record->labels()->all(),
            'correlation_id' => $record->correlationId(),
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
        ];
    }

    public function toDomain(AuditRecordModel $model): AuditRecord
    {
        $reference = new AuditableReference(
            $this->string($model->getAttribute('auditable_type')),
            $this->string($model->getAttribute('auditable_id')),
        );

        $origin = null;
        $originType = $model->getAttribute('origin_type');

        if (is_string($originType) && $originType !== '') {
            $origin = new Actor(
                ActorType::from($originType),
                $this->nullableString($model->getAttribute('origin_id')),
                $this->nullableString($model->getAttribute('origin_label')),
            );
        }

        return new AuditRecord(
            auditable: $reference,
            event: ChangeType::from($this->string($model->getAttribute('event'))),
            diff: $this->diff($model->getAttribute('changes')),
            actor: new Actor(
                ActorType::from($this->string($model->getAttribute('actor_type'))),
                $this->nullableString($model->getAttribute('actor_id')),
                $this->nullableString($model->getAttribute('actor_label')),
            ),
            origin: $origin,
            labels: $this->labels($model->getAttribute('labels')),
            occurredAt: new DateTimeImmutable($this->string($model->getAttribute('occurred_at'))),
            correlationId: $this->nullableString($model->getAttribute('correlation_id')),
            id: (int) $model->getAttribute('id'),
        );
    }

    private function diff(mixed $changes): Diff
    {
        if (! is_array($changes) || $changes === []) {
            return Diff::empty();
        }

        $fields = [];

        foreach ($changes as $name => $pair) {
            $old = is_array($pair) ? ($pair['old'] ?? null) : null;
            $new = is_array($pair) ? ($pair['new'] ?? null) : null;

            $fields[] = new FieldDiff((string) $name, $this->value($old), $this->value($new));
        }

        return Diff::fromFields($fields);
    }

    private function labels(mixed $labels): LabelSnapshot
    {
        if (! is_array($labels)) {
            return LabelSnapshot::empty();
        }

        $map = [];

        foreach ($labels as $field => $label) {
            $map[(string) $field] = is_scalar($label) ? (string) $label : '';
        }

        return new LabelSnapshot($map);
    }

    /**
     * @return scalar|array<array-key, mixed>|null
     */
    private function value(mixed $value): string|int|float|bool|array|null
    {
        if ($value === null || is_scalar($value) || is_array($value)) {
            return $value;
        }

        return null;
    }

    private function string(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private function nullableString(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
