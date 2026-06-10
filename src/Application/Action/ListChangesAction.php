<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Action;

use DateTimeImmutable;
use Exception;
use Yammi\AuditLog\Application\DTO\AuditFilterData;
use Yammi\AuditLog\Application\DTO\ChangeListData;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;

final class ListChangesAction
{
    private const PER_PAGE = 25;

    public function __construct(
        private readonly AuditRecordRepository $repository,
    ) {}

    public function __invoke(AuditFilterData $filters): ChangeListData
    {
        $criteria = new AuditCriteria(
            auditableType: $filters->type !== '' ? $filters->type : null,
            event: ChangeType::tryFrom($filters->event),
            actorType: ActorType::tryFrom($filters->actorType),
            actorLabel: $filters->actor !== '' ? $filters->actor : null,
            from: $this->date($filters->from),
            to: $this->date($filters->to),
        );

        $paged = $this->repository->paginate($criteria, max(1, $filters->page), self::PER_PAGE);

        $entries = [];

        foreach ($paged->records as $record) {
            $entries[] = TimelineEntryData::fromRecord($record);
        }

        return new ChangeListData(
            entries: $entries,
            total: $paged->total,
            page: $paged->page,
            perPage: $paged->perPage,
            lastPage: $paged->lastPage(),
            models: $this->repository->distinctModels(),
            actorTypes: $this->repository->distinctActorTypes(),
            events: array_map(static fn (ChangeType $type): string => $type->value, ChangeType::cases()),
            filters: $filters,
        );
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
