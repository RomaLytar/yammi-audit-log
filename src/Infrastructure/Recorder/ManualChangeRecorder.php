<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Recorder;

use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Application\Action\RecordChangeAction;
use Yammi\AuditLog\Application\DTO\ChangeData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;

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

        return $record === null ? null : TimelineEntryData::fromRecord($record);
    }
}
