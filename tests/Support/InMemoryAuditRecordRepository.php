<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use DateTimeImmutable;
use Yammi\AuditLog\Application\Contract\AuditLogQuery;
use Yammi\AuditLog\Application\Contract\AuditStatsQuery;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;
use Yammi\AuditLog\Domain\Audit\Query\PagedRecords;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

final class InMemoryAuditRecordRepository implements AuditLogQuery, AuditRecordRepository, AuditStatsQuery
{
    /** @var list<AuditRecord> */
    public array $saved = [];

    public function save(AuditRecord $record): void
    {
        $this->saved[] = $record;
    }

    public function timelineFor(AuditableReference $auditable, int $limit = 50): array
    {
        $matches = array_values(array_filter(
            $this->saved,
            static fn (AuditRecord $record): bool => $record->auditable()->equals($auditable),
        ));

        return array_slice(array_reverse($matches), 0, $limit);
    }

    public function deleteOlderThan(DateTimeImmutable $cutoff): int
    {
        $before = count($this->saved);

        $this->saved = array_values(array_filter(
            $this->saved,
            static fn (AuditRecord $record): bool => $record->occurredAt() >= $cutoff,
        ));

        return $before - count($this->saved);
    }

    public function paginate(AuditCriteria $criteria, int $page = 1, int $perPage = 25): PagedRecords
    {
        $matched = array_values(array_filter(
            $this->saved,
            fn (AuditRecord $record): bool => $this->matches($record, $criteria),
        ));

        $matched = array_reverse($matched);
        $total = count($matched);
        $slice = array_slice($matched, ($page - 1) * $perPage, $perPage);

        return new PagedRecords(array_values($slice), $total, $page, $perPage);
    }

    public function all(AuditCriteria $criteria, int $limit): array
    {
        $matched = array_values(array_filter(
            $this->saved,
            fn (AuditRecord $record): bool => $this->matches($record, $criteria),
        ));

        return array_slice(array_reverse($matched), 0, $limit);
    }

    public function chain(string $correlationId): array
    {
        return array_values(array_filter(
            $this->saved,
            static fn (AuditRecord $record): bool => $record->correlationId() === $correlationId,
        ));
    }

    public function historyFor(AuditableReference $auditable, DateTimeImmutable $until, int $limit = 1000): array
    {
        $matches = array_values(array_filter(
            $this->saved,
            static fn (AuditRecord $record): bool => $record->auditable()->equals($auditable)
                && $record->occurredAt() <= $until,
        ));

        usort(
            $matches,
            static fn (AuditRecord $a, AuditRecord $b): int => $a->occurredAt() <=> $b->occurredAt(),
        );

        return array_slice($matches, 0, $limit);
    }

    public function chainSizes(array $correlationIds): array
    {
        $counts = [];

        foreach ($this->saved as $record) {
            $id = $record->correlationId();

            if ($id !== null && in_array($id, $correlationIds, true)) {
                $counts[$id] = ($counts[$id] ?? 0) + 1;
            }
        }

        return $counts;
    }

    public function countNoise(): int
    {
        return count(array_filter($this->saved, static fn (AuditRecord $record): bool => $record->isNoise()));
    }

    public function countAll(): int
    {
        return count($this->saved);
    }

    public function countSince(DateTimeImmutable $cutoff): int
    {
        return count(array_filter(
            $this->saved,
            static fn (AuditRecord $record): bool => $record->occurredAt() >= $cutoff,
        ));
    }

    public function distinctModels(): array
    {
        $models = [];

        foreach ($this->saved as $record) {
            $models[$record->auditable()->type] = true;
        }

        $keys = array_keys($models);
        sort($keys);

        return array_values($keys);
    }

    public function distinctActorTypes(): array
    {
        $types = [];

        foreach ($this->saved as $record) {
            $types[$record->actor()->type->value] = true;
        }

        $keys = array_keys($types);
        sort($keys);

        return array_values($keys);
    }

    public function count(AuditCriteria $criteria): int
    {
        return count(array_filter(
            $this->saved,
            fn (AuditRecord $record): bool => $this->matches($record, $criteria),
        ));
    }

    public function eventBreakdown(AuditCriteria $criteria): array
    {
        return $this->breakdown($criteria, static fn (AuditRecord $record): string => $record->event()->value);
    }

    public function actorTypeBreakdown(AuditCriteria $criteria): array
    {
        return $this->breakdown($criteria, static fn (AuditRecord $record): string => $record->actor()->type->value);
    }

    public function modelBreakdown(AuditCriteria $criteria, int $limit = 10): array
    {
        return array_slice(
            $this->breakdown($criteria, static fn (AuditRecord $record): string => $record->auditable()->type),
            0,
            $limit,
            true,
        );
    }

    public function dailyCounts(AuditCriteria $criteria, DateTimeImmutable $from, int $days): array
    {
        $start = $from->setTime(0, 0);

        $out = [];

        for ($i = 0; $i < $days; $i++) {
            $out[$start->modify("+{$i} days")->format('Y-m-d')] = 0;
        }

        foreach ($this->saved as $record) {
            if (! $this->matches($record, $criteria)) {
                continue;
            }

            $day = $record->occurredAt()->format('Y-m-d');

            if (array_key_exists($day, $out)) {
                $out[$day]++;
            }
        }

        return $out;
    }

    /**
     * @param  callable(AuditRecord): string  $key
     * @return array<string, int>
     */
    private function breakdown(AuditCriteria $criteria, callable $key): array
    {
        $counts = [];

        foreach ($this->saved as $record) {
            if ($this->matches($record, $criteria)) {
                $counts[$key($record)] = ($counts[$key($record)] ?? 0) + 1;
            }
        }

        arsort($counts);

        return $counts;
    }

    private function matches(AuditRecord $record, AuditCriteria $criteria): bool
    {
        return $this->same($criteria->auditableType, $record->auditable()->type)
            && $this->same($criteria->event, $record->event())
            && $this->same($criteria->actorType, $record->actor()->type)
            && $this->contains($criteria->actorLabel, $record->actor()->displayLabel())
            && ($criteria->onlyNoise === null || $record->isNoise() === $criteria->onlyNoise)
            && ($criteria->from === null || $record->occurredAt() >= $criteria->from)
            && ($criteria->to === null || $record->occurredAt() <= $criteria->to)
            && $this->matchesSearch($record, $criteria->search);
    }

    private function matchesSearch(AuditRecord $record, ?string $search): bool
    {
        if ($search === null) {
            return true;
        }

        return $record->auditable()->id === $search
            || $this->contains($search, (string) json_encode($record->diff()->toArray()));
    }

    private function same(mixed $expected, mixed $actual): bool
    {
        return $expected === null || $expected === $actual;
    }

    private function contains(?string $needle, string $haystack): bool
    {
        return $needle === null || str_contains(strtolower($haystack), strtolower($needle));
    }
}
