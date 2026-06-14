<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Anomaly;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Application\DTO\Anomaly\AnomalyData;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Infrastructure\Anomaly\AnomalyScanner;
use Yammi\AuditLog\Tests\TestCase;

final class CascadeAnomalyTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.anomalies.cascade_threshold', 3);
        $app['config']->set('audit-log.anomalies.rate_threshold', 0);
        $app['config']->set('audit-log.anomalies.delete_threshold', 0);
        $app['config']->set('audit-log.anomalies.off_hours', []);
    }

    public function test_a_correlation_above_the_threshold_is_flagged_as_a_cascade(): void
    {
        $correlation = '550e8400-e29b-41d4-a716-446655440000';

        foreach (range(1, 4) as $i) {
            $this->seedChange((string) $i, $correlation, 'App\\Models\\Order');
        }

        $cascade = $this->cascadeFindings();

        $this->assertCount(1, $cascade);
        $this->assertSame(AnomalyData::RULE_CASCADE, $cascade[0]->rule);
        $this->assertSame(4, $cascade[0]->count);
        $this->assertStringContainsString('cascade', strtolower($cascade[0]->description));
    }

    public function test_a_correlation_below_the_threshold_is_not_flagged(): void
    {
        $correlation = '550e8400-e29b-41d4-a716-446655440001';

        foreach (range(1, 2) as $i) {
            $this->seedChange((string) $i, $correlation, 'App\\Models\\Order');
        }

        $this->assertSame([], $this->cascadeFindings());
    }

    public function test_the_rule_is_disabled_when_the_threshold_is_zero(): void
    {
        $this->app['config']->set('audit-log.anomalies.cascade_threshold', 0);

        $correlation = '550e8400-e29b-41d4-a716-446655440002';

        foreach (range(1, 10) as $i) {
            $this->seedChange((string) $i, $correlation, 'App\\Models\\Order');
        }

        $this->assertSame([], $this->cascadeFindings());
    }

    /**
     * @return list<AnomalyData>
     */
    private function cascadeFindings(): array
    {
        $findings = $this->app->make(AnomalyScanner::class)->scan(60);

        return array_values(array_filter(
            $findings,
            static fn (AnomalyData $finding): bool => $finding->rule === AnomalyData::RULE_CASCADE,
        ));
    }

    private function seedChange(string $id, string $correlation, string $type): void
    {
        $this->app->make(AuditRecordRepository::class)->save(new AuditRecord(
            auditable: AuditableReference::to($type, $id),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::job('App\\Jobs\\Sync', 'sync-job'),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('-5 minutes'),
            correlationId: $correlation,
        ));
    }
}
