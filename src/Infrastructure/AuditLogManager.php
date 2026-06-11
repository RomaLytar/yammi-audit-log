<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure;

use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Application\DTO\TimelineData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Infrastructure\Reader\AuditReader;
use Yammi\AuditLog\Infrastructure\Recorder\ManualChangeRecorder;

/**
 * The facade root: read timelines and record manual changes.
 *
 * @internal
 */
final class AuditLogManager
{
    public function __construct(
        private readonly AuditReader $reader,
        private readonly ManualChangeRecorder $recorder,
    ) {}

    public function for(Model|string $auditable, int|string|null $id = null, int $limit = 50): TimelineData
    {
        return $this->reader->for($auditable, $id, $limit);
    }

    /**
     * @param  array<string, scalar|array<array-key, mixed>|null>  $before
     * @param  array<string, scalar|array<array-key, mixed>|null>  $after
     */
    public function record(
        Model|string $auditable,
        int|string|null $id,
        ChangeType|string $event,
        array $before = [],
        array $after = [],
    ): ?TimelineEntryData {
        return $this->recorder->record($auditable, $id, $event, $before, $after);
    }
}
