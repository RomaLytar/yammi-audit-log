<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service;

use DateTimeImmutable;
use Exception;
use Yammi\AuditLog\Application\DTO\Audit\AuditFilterData;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;

/**
 * Turns the parsed dashboard filters into domain criteria; the single place
 * that normalises the date range and empty values.
 *
 * @internal
 */
final class CriteriaFactory
{
    public function fromFilters(AuditFilterData $filters, ?bool $onlyNoise = null): AuditCriteria
    {
        [$from, $to] = $this->dateRange($filters->from, $filters->to);

        return new AuditCriteria(
            auditableType: $filters->type !== '' ? $filters->type : null,
            event: ChangeType::tryFrom($filters->event),
            actorType: ActorType::tryFrom($filters->actorType),
            actorLabel: $filters->actor !== '' ? $filters->actor : null,
            from: $from,
            to: $to,
            onlyNoise: $onlyNoise,
            search: $filters->search !== '' ? $filters->search : null,
            auditableId: $filters->auditableId !== '' ? $filters->auditableId : null,
            field: $filters->field !== '' ? $filters->field : null,
            valueFrom: $filters->valueFrom !== '' ? $filters->valueFrom : null,
            valueTo: $filters->valueTo !== '' ? $filters->valueTo : null,
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
