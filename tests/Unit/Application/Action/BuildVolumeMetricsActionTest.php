<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\BuildVolumeMetricsAction;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\FixedClock;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;

final class BuildVolumeMetricsActionTest extends TestCase
{
    public function test_it_summarises_totals_rate_and_projection(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->recordOn('2020-01-01'));
        $repository->save($this->recordOn('2026-05-20'));
        $repository->save($this->recordOn('2026-05-25'));
        $repository->save($this->recordOn('2026-05-30'));

        $action = new BuildVolumeMetricsAction(
            $repository,
            new FixedClock(new DateTimeImmutable('2026-06-01T00:00:00+00:00')),
        );

        $metrics = $action(180);

        $this->assertSame(4, $metrics->total);
        $this->assertSame(3, $metrics->last30Days);
        $this->assertSame(0.1, $metrics->perDay);
        $this->assertSame(18, $metrics->projectedRows);
    }

    public function test_disabled_retention_has_no_projection(): void
    {
        $action = new BuildVolumeMetricsAction(
            new InMemoryAuditRecordRepository,
            new FixedClock(new DateTimeImmutable('2026-06-01T00:00:00+00:00')),
        );

        $metrics = $action(0);

        $this->assertSame(0, $metrics->total);
        $this->assertSame(0.0, $metrics->perDay);
        $this->assertNull($metrics->projectedRows);
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
