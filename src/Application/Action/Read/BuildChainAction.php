<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action\Read;

use Yammi\AuditLog\Application\Contract\Query\AuditLogQuery;
use Yammi\AuditLog\Application\DTO\Audit\ChainData;
use Yammi\AuditLog\Application\DTO\Audit\ChainNodeData;
use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;

/** @internal */
final class BuildChainAction
{
    private const ROOTLESS = "\0rootless";

    public function __construct(
        private readonly AuditLogQuery $query,
    ) {}

    public function __invoke(string $correlationId): ?ChainData
    {
        $records = $this->query->chain($correlationId);

        if ($records === []) {
            return null;
        }

        $entries = [];
        $models = [];

        foreach ($records as $record) {
            $entries[] = TimelineEntryData::fromRecord($record);
            $models[$record->auditable()->type] = true;
        }

        $root = $entries[0];

        return new ChainData(
            correlationId: $correlationId,
            entries: $entries,
            modelCount: count($models),
            rootActorLabel: $root->actorLabel,
            rootModel: $root->model(),
            tree: $this->buildTree($records),
        );
    }

    /**
     * Group the changes by the span that made them, then nest each span under
     * the span that caused it. Spans whose parent is absent (a root unit of work,
     * or a parent that made no audited change) become roots of the tree. Records
     * with no span (written before span tracking) collapse into a single root.
     *
     * @param  list<AuditRecord>  $records
     * @return list<ChainNodeData>
     */
    private function buildTree(array $records): array
    {
        /** @var array<string, list<AuditRecord>> $bySpan */
        $bySpan = [];
        /** @var list<string> $order */
        $order = [];
        /** @var array<string, ?string> $parentOf */
        $parentOf = [];
        /** @var array<string, ?string> $spanIdOf */
        $spanIdOf = [];

        foreach ($records as $record) {
            $spanId = $record->spanId();
            $key = $spanId ?? self::ROOTLESS;

            if (! isset($bySpan[$key])) {
                $bySpan[$key] = [];
                $order[] = $key;
                $parentOf[$key] = $record->parentSpanId();
                $spanIdOf[$key] = $spanId;
            }

            $bySpan[$key][] = $record;
        }

        /** @var array<string, list<string>> $childrenOf */
        $childrenOf = [];
        /** @var list<string> $roots */
        $roots = [];

        foreach ($order as $key) {
            $parent = $parentOf[$key];

            if ($parent !== null && isset($bySpan[$parent])) {
                $childrenOf[$parent][] = $key;
            } else {
                $roots[] = $key;
            }
        }

        $tree = [];

        foreach ($roots as $key) {
            $tree[] = $this->buildNode($key, 0, $bySpan, $childrenOf, $parentOf, $spanIdOf);
        }

        return $tree;
    }

    /**
     * @param  array<string, list<AuditRecord>>  $bySpan
     * @param  array<string, list<string>>  $childrenOf
     * @param  array<string, ?string>  $parentOf
     * @param  array<string, ?string>  $spanIdOf
     */
    private function buildNode(string $key, int $depth, array $bySpan, array $childrenOf, array $parentOf, array $spanIdOf): ChainNodeData
    {
        $records = $bySpan[$key];
        $entries = TimelineEntryData::fromRecords($records);

        $children = [];

        foreach ($childrenOf[$key] ?? [] as $childKey) {
            $children[] = $this->buildNode($childKey, $depth + 1, $bySpan, $childrenOf, $parentOf, $spanIdOf);
        }

        $first = $records[0];

        return new ChainNodeData(
            spanId: $spanIdOf[$key],
            parentSpanId: $parentOf[$key],
            entries: $entries,
            children: $children,
            depth: $depth,
            actorType: $first->actor()->type->value,
            actorLabel: $first->actor()->displayLabel(),
            originLabel: $first->origin()?->displayLabel(),
            model: $entries[0]->model(),
        );
    }
}
