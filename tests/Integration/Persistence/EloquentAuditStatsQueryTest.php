<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Persistence;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Application\Contract\Query\AuditStatsQuery;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Query\AuditCriteria;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\TestCase;

final class EloquentAuditStatsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_breakdowns_and_counts_respect_criteria(): void
    {
        $this->saveRecord(ChangeType::Created, 'App\\Models\\Order', '2026-01-01');
        $this->saveRecord(ChangeType::Updated, 'App\\Models\\Order', '2026-01-02');
        $this->saveRecord(ChangeType::Updated, 'App\\Models\\Invoice', '2026-01-03');

        $stats = $this->stats();

        $this->assertSame(3, $stats->count(new AuditCriteria));
        $this->assertSame(2, $stats->count(new AuditCriteria(event: ChangeType::Updated)));
        $this->assertSame(['updated' => 2, 'created' => 1], $stats->eventBreakdown(new AuditCriteria));
        $this->assertSame(['system' => 3], $stats->actorTypeBreakdown(new AuditCriteria));
        $this->assertSame(
            ['App\\Models\\Order' => 2, 'App\\Models\\Invoice' => 1],
            $stats->modelBreakdown(new AuditCriteria),
        );
        $this->assertSame(
            ['App\\Models\\Order' => 2],
            $stats->modelBreakdown(new AuditCriteria, 1),
        );
    }

    public function test_daily_counts_are_zero_filled_and_grouped(): void
    {
        $this->saveRecord(ChangeType::Created, 'App\\Models\\Order', '2026-01-02');
        $this->saveRecord(ChangeType::Created, 'App\\Models\\Order', '2026-01-02');
        $this->saveRecord(ChangeType::Created, 'App\\Models\\Order', '2026-01-04');

        $days = $this->stats()->dailyCounts(new AuditCriteria, new DateTimeImmutable('2026-01-01'), 5);

        $this->assertSame([
            '2026-01-01' => 0,
            '2026-01-02' => 2,
            '2026-01-03' => 0,
            '2026-01-04' => 1,
            '2026-01-05' => 0,
        ], $days);
    }

    public function test_top_cascades_ranks_correlations_by_write_volume(): void
    {
        $heavy = '11111111-1111-1111-1111-111111111111';
        $light = '22222222-2222-2222-2222-222222222222';

        $this->saveRecord(ChangeType::Updated, 'App\\Models\\Order', '2026-01-01', $heavy, 2);
        $this->saveRecord(ChangeType::Updated, 'App\\Models\\Invoice', '2026-01-01', $heavy, 3);
        $this->saveRecord(ChangeType::Updated, 'App\\Models\\Order', '2026-01-01', $heavy, 1);
        $this->saveRecord(ChangeType::Updated, 'App\\Models\\Order', '2026-01-02', $light, 0);
        $this->saveRecord(ChangeType::Updated, 'App\\Models\\Order', '2026-01-02');

        $cascades = $this->stats()->topCascades(new AuditCriteria, 10);

        $this->assertCount(2, $cascades);
        $this->assertSame($heavy, $cascades[0]['correlation_id']);
        $this->assertSame(3, $cascades[0]['writes']);
        $this->assertSame(2, $cascades[0]['models']);
        $this->assertSame(3, $cascades[0]['depth']);
        $this->assertSame($light, $cascades[1]['correlation_id']);
        $this->assertSame(1, $cascades[1]['writes']);
    }

    private function stats(): AuditStatsQuery
    {
        return $this->app->make(AuditStatsQuery::class);
    }

    private function saveRecord(
        ChangeType $event,
        string $type,
        string $date,
        ?string $correlation = null,
        int $depth = 0,
    ): void {
        $this->app->make(AuditRecordRepository::class)->save(new AuditRecord(
            auditable: AuditableReference::to($type, 1),
            event: $event,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable($date.'T10:00:00+00:00'),
            correlationId: $correlation,
            chainDepth: $depth,
        ));
    }
}
