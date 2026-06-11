<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO;

/** @internal */
final class AuditFilterData
{
    public function __construct(
        public readonly string $type = '',
        public readonly string $event = '',
        public readonly string $actorType = '',
        public readonly string $actor = '',
        public readonly string $from = '',
        public readonly string $to = '',
        public readonly int $page = 1,
        public readonly string $search = '',
    ) {}

    public function isActive(): bool
    {
        return $this->type !== ''
            || $this->event !== ''
            || $this->actorType !== ''
            || $this->actor !== ''
            || $this->from !== ''
            || $this->to !== ''
            || $this->search !== '';
    }
}
