<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Pipeline;

use Yammi\AuditLog\Application\DTO\ChangeData;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;

/** @internal */
final class RecordChangeContext
{
    public function __construct(
        public readonly ChangeData $change,
        public readonly Diff $diff,
        public readonly ?Actor $actor,
        public readonly ?Actor $origin,
        public readonly LabelSnapshot $labels,
        public readonly bool $isNoise = false,
    ) {}

    public static function start(ChangeData $change): self
    {
        return new self($change, Diff::empty(), null, null, LabelSnapshot::empty());
    }

    public function withDiff(Diff $diff): self
    {
        return new self($this->change, $diff, $this->actor, $this->origin, $this->labels, $this->isNoise);
    }

    public function withNoise(bool $isNoise): self
    {
        return new self($this->change, $this->diff, $this->actor, $this->origin, $this->labels, $isNoise);
    }

    public function withActor(Actor $actor, ?Actor $origin): self
    {
        return new self($this->change, $this->diff, $actor, $origin, $this->labels, $this->isNoise);
    }

    public function withLabels(LabelSnapshot $labels): self
    {
        return new self($this->change, $this->diff, $this->actor, $this->origin, $labels, $this->isNoise);
    }
}
