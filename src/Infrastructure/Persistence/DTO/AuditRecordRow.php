<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\DTO;

/**
 * Typed representation of a single audit_log row. The mapper builds it from a
 * domain record; toArray() is the single, localized conversion to the column
 * array Eloquent needs.
 *
 * @internal
 */
final class AuditRecordRow
{
    /**
     * @param  array<string, array{old: scalar|array<array-key, mixed>|null, new: scalar|array<array-key, mixed>|null}>  $changes
     * @param  array<string, string>  $labels
     * @param  array<string, string>  $context
     */
    public function __construct(
        public readonly string $auditableType,
        public readonly string $auditableId,
        public readonly string $event,
        public readonly array $changes,
        public readonly string $actorType,
        public readonly ?string $actorId,
        public readonly ?string $actorLabel,
        public readonly ?string $originType,
        public readonly ?string $originId,
        public readonly ?string $originLabel,
        public readonly array $labels,
        public readonly ?string $correlationId,
        public readonly bool $isNoise,
        public readonly string $occurredAt,
        public readonly array $context = [],
        public readonly int $chainDepth = 0,
        public readonly ?string $tenantId = null,
        public readonly ?string $reason = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'auditable_type' => $this->auditableType,
            'auditable_id' => $this->auditableId,
            'event' => $this->event,
            'changes' => $this->changes,
            'actor_type' => $this->actorType,
            'actor_id' => $this->actorId,
            'actor_label' => $this->actorLabel,
            'origin_type' => $this->originType,
            'origin_id' => $this->originId,
            'origin_label' => $this->originLabel,
            'labels' => $this->labels,
            'correlation_id' => $this->correlationId,
            'is_noise' => $this->isNoise,
            'occurred_at' => $this->occurredAt,
            'created_at' => $this->occurredAt,
            'context' => $this->context,
            'chain_depth' => $this->chainDepth,
            'tenant_id' => $this->tenantId,
            'reason' => $this->reason,
        ];
    }
}
