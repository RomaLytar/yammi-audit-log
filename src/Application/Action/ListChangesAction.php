<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action;

use DateTimeImmutable;
use Exception;
use Yammi\AuditLog\Application\Contract\AuditLogQuery;
use Yammi\AuditLog\Application\DTO\AuditFilterData;
use Yammi\AuditLog\Application\DTO\ChangeListData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;

/** @internal */
final class ListChangesAction
{
    private const PER_PAGE = 25;

    public function __construct(
        private readonly AuditLogQuery $query,
    ) {}

    public function __invoke(AuditFilterData $filters, ?bool $onlyNoise = null): ChangeListData
    {
        [$from, $to] = $this->dateRange($filters->from, $filters->to);

        $criteria = new AuditCriteria(
            auditableType: $filters->type !== '' ? $filters->type : null,
            event: ChangeType::tryFrom($filters->event),
            actorType: ActorType::tryFrom($filters->actorType),
            actorLabel: $filters->actor !== '' ? $filters->actor : null,
            from: $from,
            to: $to,
            onlyNoise: $onlyNoise,
        );

        $paged = $this->query->paginate($criteria, max(1, $filters->page), self::PER_PAGE);

        $entries = [];
        $correlationIds = [];

        foreach ($paged->records as $record) {
            $entry = TimelineEntryData::fromRecord($record);
            $entries[] = $entry;

            if ($entry->correlationId !== null) {
                $correlationIds[$entry->correlationId] = true;
            }
        }

        return new ChangeListData(
            entries: $entries,
            total: $paged->total,
            page: $paged->page,
            perPage: $paged->perPage,
            lastPage: $paged->lastPage(),
            models: $this->query->distinctModels(),
            actorTypes: $this->query->distinctActorTypes(),
            events: array_map(static fn (ChangeType $type): string => $type->value, ChangeType::cases()),
            filters: $filters,
            correlationSizes: $this->query->chainSizes(array_keys($correlationIds)),
        );
    }

    /**
     * Normalise the range so the end is never earlier than the start.
     *
     * @return array{0: ?DateTimeImmutable, 1: ?DateTimeImmutable}
     */
    private function dateRange(string $from, string $to): array
    {
        $start = $this->date($from);
        $end = $this->date($to);

        if ($start !== null && $end !== null && $start > $end) {
            return [$end, $start];
        }

        return [$start, $end];
    }

    private function date(string $value): ?DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }
}
