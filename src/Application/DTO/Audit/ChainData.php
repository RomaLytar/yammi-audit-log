<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO\Audit;

final class ChainData
{
    /**
     * @param  list<TimelineEntryData>  $entries
     * @param  list<ChainNodeData>  $tree
     */
    public function __construct(
        public readonly string $correlationId,
        public readonly array $entries,
        public readonly int $modelCount,
        public readonly string $rootActorLabel,
        public readonly string $rootModel,
        public readonly array $tree = [],
    ) {}

    public function count(): int
    {
        return count($this->entries);
    }
}
