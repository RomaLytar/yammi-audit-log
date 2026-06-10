<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO;

/** @internal */
final class ChainData
{
    /**
     * @param  list<TimelineEntryData>  $entries
     */
    public function __construct(
        public readonly string $correlationId,
        public readonly array $entries,
        public readonly int $modelCount,
        public readonly string $rootActorLabel,
        public readonly string $rootModel,
    ) {}

    public function count(): int
    {
        return count($this->entries);
    }
}
