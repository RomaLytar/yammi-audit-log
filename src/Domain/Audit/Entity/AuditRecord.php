<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\Entity;

use DateTimeImmutable;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;

final class AuditRecord
{
    /**
     * @param  array<string, string>  $context
     */
    public function __construct(
        private readonly AuditableReference $auditable,
        private readonly ChangeType $event,
        private readonly Diff $diff,
        private readonly Actor $actor,
        private readonly ?Actor $origin,
        private readonly LabelSnapshot $labels,
        private readonly DateTimeImmutable $occurredAt,
        private readonly ?string $correlationId = null,
        private readonly bool $isNoise = false,
        private readonly ?int $id = null,
        private readonly array $context = [],
        private readonly int $chainDepth = 0,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function auditable(): AuditableReference
    {
        return $this->auditable;
    }

    public function event(): ChangeType
    {
        return $this->event;
    }

    public function diff(): Diff
    {
        return $this->diff;
    }

    public function actor(): Actor
    {
        return $this->actor;
    }

    public function origin(): ?Actor
    {
        return $this->origin;
    }

    public function labels(): LabelSnapshot
    {
        return $this->labels;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function correlationId(): ?string
    {
        return $this->correlationId;
    }

    /**
     * Request metadata captured with the change (ip, url, method, user agent).
     *
     * @return array<string, string>
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * Nesting inside the unit of work: 0 for the root request/command, +1
     * per job dispatched inside a job.
     */
    public function chainDepth(): int
    {
        return $this->chainDepth;
    }

    /**
     * A change that only touched ignored attributes (e.g. timestamps) — a no-op
     * write, usually a sign of a double update that changed nothing real.
     */
    public function isNoise(): bool
    {
        return $this->isNoise;
    }

    public function hasIdentifiedActor(): bool
    {
        return ! $this->actor->isAnonymous();
    }
}
