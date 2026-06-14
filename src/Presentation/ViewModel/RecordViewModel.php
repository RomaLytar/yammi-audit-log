<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Yammi\AuditLog\Application\DTO\Audit\RecordViewData;
use Yammi\AuditLog\Application\DTO\Audit\RelatedChangeData;

/**
 * Presents the single-record page: the record's own timeline and the
 * related changes with their connection badges.
 *
 * @internal
 */
final class RecordViewModel
{
    public function __construct(
        private readonly RecordViewData $view,
        private readonly ?string $timezone = null,
    ) {}

    public function model(): string
    {
        return $this->view->model();
    }

    public function type(): string
    {
        return $this->view->auditableType;
    }

    public function id(): string
    {
        return $this->view->auditableId;
    }

    public function isEmpty(): bool
    {
        return $this->view->isEmpty();
    }

    public function changeCount(): int
    {
        return count($this->view->entries);
    }

    public function referenceField(): string
    {
        return $this->view->referenceField;
    }

    /**
     * @return list<TimelineEntryViewModel>
     */
    public function entries(): array
    {
        $entries = [];

        foreach ($this->view->entries as $entry) {
            $entries[] = new TimelineEntryViewModel($entry, 0, null, $this->timezone);
        }

        return $entries;
    }

    /**
     * @return list<array{entry: TimelineEntryViewModel, via: string, viaLabel: string, viaIcon: string}>
     */
    public function related(): array
    {
        $related = [];

        foreach ($this->view->related as $item) {
            [$label, $icon] = $item->via === RelatedChangeData::VIA_CHAIN
                ? ['same action', 'git-fork']
                : ['references this record', 'link'];

            $related[] = [
                'entry' => new TimelineEntryViewModel($item->entry, 0, null, $this->timezone),
                'via' => $item->via,
                'viaLabel' => $label,
                'viaIcon' => $icon,
            ];
        }

        return $related;
    }

    public function hasRelated(): bool
    {
        return $this->view->related !== [];
    }
}
