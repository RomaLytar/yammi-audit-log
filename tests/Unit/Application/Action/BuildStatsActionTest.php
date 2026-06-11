<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\BuildStatsAction;
use Yammi\AuditLog\Application\DTO\AuditFilterData;
use Yammi\AuditLog\Application\Service\CriteriaFactory;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\FixedClock;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;

final class BuildStatsActionTest extends TestCase
{
    private InMemoryAuditRecordRepository $repository;

    private BuildStatsAction $action;

    protected function setUp(): void
    {
        $this->repository = new InMemoryAuditRecordRepository;
        $this->action = new BuildStatsAction(
            $this->repository,
            $this->repository,
            new CriteriaFactory,
            new FixedClock(new DateTimeImmutable('2026-06-01T00:00:00+00:00')),
        );
    }

    public function test_it_summarises_volume_and_breakdowns(): void
    {
        $this->repository->save($this->record(ChangeType::Created, '2020-01-01', 'App\\Models\\Order'));
        $this->repository->save($this->record(ChangeType::Created, '2026-05-20', 'App\\Models\\Order'));
        $this->repository->save($this->record(ChangeType::Updated, '2026-05-25', 'App\\Models\\Order'));
        $this->repository->save($this->record(ChangeType::Updated, '2026-05-30', 'App\\Models\\Invoice'));

        $stats = ($this->action)(new AuditFilterData, 180);

        $this->assertSame(4, $stats->total);
        $this->assertSame(3, $stats->last30Days);
        $this->assertSame(0.1, $stats->perDay);
        $this->assertSame(18, $stats->projectedRows);
        $this->assertSame(['created' => 2, 'updated' => 2], $stats->byEvent);
        $this->assertSame(['system' => 4], $stats->byActorType);
        $this->assertSame(['App\\Models\\Order' => 3, 'App\\Models\\Invoice' => 1], $stats->byModel);
    }

    public function test_filters_narrow_every_number(): void
    {
        $this->repository->save($this->record(ChangeType::Created, '2026-05-20', 'App\\Models\\Order'));
        $this->repository->save($this->record(ChangeType::Updated, '2026-05-25', 'App\\Models\\Order'));

        $stats = ($this->action)(new AuditFilterData(event: 'created'), 180);

        $this->assertSame(1, $stats->total);
        $this->assertSame(1, $stats->last30Days);
        $this->assertSame(['created' => 1], $stats->byEvent);
    }

    public function test_daily_counts_cover_the_window_zero_filled(): void
    {
        $this->repository->save($this->record(ChangeType::Created, '2026-05-31', 'App\\Models\\Order'));
        $this->repository->save($this->record(ChangeType::Created, '2026-05-31', 'App\\Models\\Order'));

        $stats = ($this->action)(new AuditFilterData, 180);

        $this->assertCount(30, $stats->byDay);
        $this->assertSame(2, $stats->byDay['2026-05-31']);
        $this->assertSame(0, $stats->byDay['2026-05-30']);
        $this->assertSame('2026-05-03', array_key_first($stats->byDay));
    }

    public function test_disabled_retention_has_no_projection(): void
    {
        $stats = ($this->action)(new AuditFilterData, 0);

        $this->assertNull($stats->projectedRows);
        $this->assertSame(0.0, $stats->perDay);
    }

    private function record(ChangeType $event, string $date, string $type): AuditRecord
    {
        return new AuditRecord(
            auditable: AuditableReference::to($type, 1),
            event: $event,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable($date.'T10:00:00+00:00'),
        );
    }
}
