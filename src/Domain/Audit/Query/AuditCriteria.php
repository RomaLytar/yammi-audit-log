<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\Query;

use DateTimeImmutable;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;

final class AuditCriteria
{
    public function __construct(
        public readonly ?string $auditableType = null,
        public readonly ?ChangeType $event = null,
        public readonly ?ActorType $actorType = null,
        public readonly ?string $actorLabel = null,
        public readonly ?DateTimeImmutable $from = null,
        public readonly ?DateTimeImmutable $to = null,
        public readonly ?bool $onlyNoise = null,
        public readonly ?string $search = null,
        public readonly ?string $auditableId = null,
        public readonly ?string $field = null,
        public readonly ?string $valueFrom = null,
        public readonly ?string $valueTo = null,
    ) {}
}
