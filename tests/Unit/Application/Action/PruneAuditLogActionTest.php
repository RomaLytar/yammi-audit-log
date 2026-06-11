<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\PruneAuditLogAction;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\FixedClock;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;

final class PruneAuditLogActionTest extends TestCase
{
    public function test_it_deletes_records_older_than_the_retention_window(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->recordOn('2026-01-01'));
        $repository->save($this->recordOn('2026-05-20'));

        $action = new PruneAuditLogAction(
            $repository,
            new FixedClock(new DateTimeImmutable('2026-06-01T00:00:00+00:00')),
        );

        $deleted = $action(30);

        $this->assertSame(1, $deleted);
        $this->assertCount(1, $repository->saved);
    }

    public function test_a_zero_window_keeps_everything(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->recordOn('2020-01-01'));

        $action = new PruneAuditLogAction(
            $repository,
            new FixedClock(new DateTimeImmutable('2026-06-01T00:00:00+00:00')),
        );

        $this->assertSame(0, $action(0));
        $this->assertCount(1, $repository->saved);
    }

    public function test_a_window_below_the_minimum_prunes_as_seven_days(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->recordOn('2026-05-29'));
        $repository->save($this->recordOn('2026-05-20'));

        $action = new PruneAuditLogAction(
            $repository,
            new FixedClock(new DateTimeImmutable('2026-06-01T00:00:00+00:00')),
        );

        $this->assertSame(1, $action(1));
        $this->assertCount(1, $repository->saved);
        $this->assertSame('2026-05-29', $repository->saved[0]->occurredAt()->format('Y-m-d'));
    }

    public function test_a_window_above_the_maximum_prunes_as_9999_days(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->recordOn('1998-01-01'));
        $repository->save($this->recordOn('2000-01-01'));

        $action = new PruneAuditLogAction(
            $repository,
            new FixedClock(new DateTimeImmutable('2026-06-01T00:00:00+00:00')),
        );

        $this->assertSame(1, $action(20000));
        $this->assertCount(1, $repository->saved);
        $this->assertSame('2000-01-01', $repository->saved[0]->occurredAt()->format('Y-m-d'));
    }

    public function test_the_clamp_bounds_are_exposed(): void
    {
        $this->assertSame(7, PruneAuditLogAction::clampDays(1));
        $this->assertSame(7, PruneAuditLogAction::clampDays(7));
        $this->assertSame(180, PruneAuditLogAction::clampDays(180));
        $this->assertSame(9999, PruneAuditLogAction::clampDays(9999));
        $this->assertSame(9999, PruneAuditLogAction::clampDays(10000));
    }

    private function recordOn(string $date): AuditRecord
    {
        return new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 1),
            event: ChangeType::Created,
            diff: Diff::empty(),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable($date.'T10:00:00+00:00'),
        );
    }
}
