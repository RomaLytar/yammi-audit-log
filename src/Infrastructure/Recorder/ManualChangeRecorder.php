<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Recorder;

use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Application\Action\RecordChangeAction;
use Yammi\AuditLog\Application\DTO\ChangeData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;
use Yammi\AuditLog\Infrastructure\Alert\AlertDispatcher;
use Yammi\AuditLog\Infrastructure\Stream\ChangeStreamer;

/**
 * Records a change that Eloquent events cannot see — mass updates, raw SQL,
 * pivot syncs — through the exact same pipeline as captured changes, so
 * redaction, actor attribution, labels and correlation all apply.
 *
 * @internal
 */
final class ManualChangeRecorder
{
    public function __construct(
        private readonly RecordChangeAction $action,
        private readonly AlertDispatcher $alerts,
        private readonly ?ChangeStreamer $stream = null,
    ) {}

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
        $type = $event instanceof ChangeType ? $event : ChangeType::tryFrom($event);

        if ($type === null) {
            throw InvalidAuditData::unknownEvent(is_string($event) ? $event : '');
        }

        [$class, $key] = $auditable instanceof Model
            ? [$auditable->getMorphClass(), (string) $auditable->getKey()]
            : [$auditable, $id === null ? '' : (string) $id];

        $record = ($this->action)(new ChangeData($class, $key, $type, $before, $after));

        if ($record === null) {
            return null;
        }

        $this->alerts->inspect($record);
        $this->stream?->push($record);

        return TimelineEntryData::fromRecord($record);
    }

    public function recordAccess(Model|string $auditable, int|string|null $id = null): ?TimelineEntryData
    {
        return $this->record($auditable, $id, ChangeType::Accessed);
    }
}
