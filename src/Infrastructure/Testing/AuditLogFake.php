<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Testing;

use Closure;
use DateTimeImmutable;
use PHPUnit\Framework\Assert;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;

/**
 * In-memory stand-in for the audit repository, installed by AuditLog::fake().
 * It collects every record the capture pipeline would have persisted, so a host
 * app can assert what its code audited without touching the database.
 */
final class AuditLogFake implements AuditRecordRepository
{
    /** @var list<AuditRecord> */
    private array $records = [];

    public function save(AuditRecord $record): void
    {
        $this->records[] = $record;
    }

    public function timelineFor(AuditableReference $auditable, int $limit = 50): array
    {
        $matches = array_values(array_filter(
            $this->records,
            static fn (AuditRecord $record): bool => $record->auditable()->equals($auditable),
        ));

        return array_slice(array_reverse($matches), 0, $limit);
    }

    public function deleteOlderThan(DateTimeImmutable $cutoff): int
    {
        return 0;
    }

    /**
     * @param  (Closure(AuditRecord): bool)|null  $matcher
     */
    public function assertRecorded(string $type, int|string|null $id = null, ChangeType|string|null $event = null, ?Closure $matcher = null): void
    {
        Assert::assertNotEmpty(
            $this->recorded($type, $id, $event, $matcher),
            "Expected an audit record for [{$type}] but none matched.",
        );
    }

    /**
     * @param  (Closure(AuditRecord): bool)|null  $matcher
     */
    public function assertNotRecorded(string $type, int|string|null $id = null, ChangeType|string|null $event = null, ?Closure $matcher = null): void
    {
        Assert::assertEmpty(
            $this->recorded($type, $id, $event, $matcher),
            "Expected no audit record for [{$type}] but one matched.",
        );
    }

    public function assertNothingRecorded(): void
    {
        Assert::assertCount(0, $this->records, 'Expected no audit records but some were recorded.');
    }

    public function assertRecordedCount(int $count): void
    {
        Assert::assertCount(
            $count,
            $this->records,
            "Expected {$count} audit record(s), but ".count($this->records).' were recorded.',
        );
    }

    /**
     * @param  (Closure(AuditRecord): bool)|null  $matcher
     * @return list<AuditRecord>
     */
    public function recorded(string $type, int|string|null $id = null, ChangeType|string|null $event = null, ?Closure $matcher = null): array
    {
        $eventValue = $event instanceof ChangeType ? $event->value : $event;

        return array_values(array_filter($this->records, function (AuditRecord $record) use ($type, $id, $eventValue, $matcher): bool {
            if ($record->auditable()->type !== $type) {
                return false;
            }

            if ($id !== null && $record->auditable()->id !== (string) $id) {
                return false;
            }

            if ($eventValue !== null && $record->event()->value !== $eventValue) {
                return false;
            }

            return $matcher === null || $matcher($record) === true;
        }));
    }

    /**
     * @return list<AuditRecord>
     */
    public function all(): array
    {
        return $this->records;
    }
}
