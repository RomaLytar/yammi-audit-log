<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Mapper;

use DateTimeImmutable;
use Yammi\AuditLog\Application\Contract\Resolver\TenantResolver;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\FieldDiff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Infrastructure\Persistence\DTO\AuditRecordRow;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Infrastructure\Tenancy\NullTenantResolver;

/** @internal */
final class AuditRecordMapper
{
    public function __construct(
        private readonly TenantResolver $tenants = new NullTenantResolver,
    ) {}

    public function toRow(AuditRecord $record): AuditRecordRow
    {
        return new AuditRecordRow(
            auditableType: $record->auditable()->type,
            auditableId: $record->auditable()->id,
            event: $record->event()->value,
            changes: $record->diff()->toArray(),
            actorType: $record->actor()->type->value,
            actorId: $record->actor()->identifier,
            actorLabel: $record->actor()->label,
            originType: $record->origin()?->type->value,
            originId: $record->origin()?->identifier,
            originLabel: $record->origin()?->label,
            labels: $record->labels()->all(),
            correlationId: $record->correlationId(),
            isNoise: $record->isNoise(),
            occurredAt: $record->occurredAt()->format('Y-m-d H:i:s'),
            reason: $record->reason(),
            context: $record->context(),
            chainDepth: $record->chainDepth(),
            tenantId: $this->tenants->resolve(),
            eventVersion: $record->eventVersion(),
            spanId: $record->spanId(),
            parentSpanId: $record->parentSpanId(),
            traceId: $record->traceId(),
        );
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
            isNoise: (bool) $model->getAttribute('is_noise'),
            id: (int) $model->getAttribute('id'),
            context: $this->stringMap($model->getAttribute('context')),
            chainDepth: max(0, (int) $model->getAttribute('chain_depth')),
            reason: $this->nullableString($model->getAttribute('reason')),
            eventVersion: max(1, (int) $model->getAttribute('event_version')),
            spanId: $this->nullableString($model->getAttribute('span_id')),
            parentSpanId: $this->nullableString($model->getAttribute('parent_span_id')),
            traceId: $this->nullableString($model->getAttribute('trace_id')),
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

    /**
     * @return array<string, string>
     */
    private function stringMap(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $out = [];

        foreach ($values as $key => $value) {
            if (is_string($value) && $value !== '') {
                $out[(string) $key] = $value;
            }
        }

        return $out;
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
