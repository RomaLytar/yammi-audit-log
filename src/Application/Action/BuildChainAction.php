<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action;

use Yammi\AuditLog\Application\Contract\AuditLogQuery;
use Yammi\AuditLog\Application\DTO\ChainData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;

/** @internal */
final class BuildChainAction
{
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
        );
    }
}
