<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\DTO\Audit;

/**
 * One span in the causation tree: the changes a single unit of work made, plus
 * the spans it caused. Nodes nest by parent span so the trace can be drawn as a
 * tree instead of a flat, depth-indented list.
 */
final class ChainNodeData
{
    /**
     * @param  list<TimelineEntryData>  $entries
     * @param  list<ChainNodeData>  $children
     */
    public function __construct(
        public readonly ?string $spanId,
        public readonly ?string $parentSpanId,
        public readonly array $entries,
        public readonly array $children,
        public readonly int $depth,
        public readonly string $actorType,
        public readonly string $actorLabel,
        public readonly ?string $originLabel,
        public readonly string $model,
    ) {}
}
