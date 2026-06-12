<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Console;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Events\AnomalyDetected;
use Yammi\AuditLog\Tests\TestCase;

final class DetectAnomaliesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.anomalies.rate_threshold', 3);
        $app['config']->set('audit-log.anomalies.delete_threshold', 2);
        $app['config']->set('audit-log.anomalies.off_hours', [0, 23]);
    }

    public function test_a_change_burst_is_flagged_and_fires_the_event(): void
    {
        Event::fake([AnomalyDetected::class]);

        foreach (range(1, 4) as $i) {
            $this->seedChange((string) $i, ChangeType::Updated, Actor::job('App\\Jobs\\Sync', 'burst-job'));
        }

        $this->artisan('audit-log:detect-anomalies')
            ->expectsOutputToContain('anomaly(ies) detected')
            ->expectsOutputToContain('rate_spike')
            ->assertSuccessful();

        Event::assertDispatched(AnomalyDetected::class, function (AnomalyDetected $event): bool {
            return $event->anomaly->rule === 'rate_spike'
                && $event->anomaly->actorLabel === 'burst-job'
                && $event->anomaly->count === 4;
        });
    }

    public function test_a_mass_deletion_is_flagged(): void
    {
        Event::fake([AnomalyDetected::class]);

        foreach (range(1, 3) as $i) {
            $this->seedChange((string) $i, ChangeType::Deleted, Actor::user('5', 'Jane Doe'));
        }

        $this->artisan('audit-log:detect-anomalies')
            ->expectsOutputToContain('mass_delete')
            ->assertSuccessful();

        Event::assertDispatched(AnomalyDetected::class, function (AnomalyDetected $event): bool {
            return $event->anomaly->rule === 'mass_delete' && $event->anomaly->count === 3;
        });
    }

    public function test_off_hours_user_activity_is_flagged(): void
    {
        Event::fake([AnomalyDetected::class]);

        $this->seedChange('1', ChangeType::Updated, Actor::user('5', 'Jane Doe'));

        $this->artisan('audit-log:detect-anomalies')
            ->expectsOutputToContain('off_hours')
            ->assertSuccessful();

        Event::assertDispatched(AnomalyDetected::class, function (AnomalyDetected $event): bool {
            return $event->anomaly->rule === 'off_hours'
                && $event->anomaly->actorLabel === 'Jane Doe';
        });
    }

    public function test_a_quiet_log_reports_no_anomalies(): void
    {
        Event::fake([AnomalyDetected::class]);

        $this->artisan('audit-log:detect-anomalies')
            ->expectsOutputToContain('No anomalies detected')
            ->assertSuccessful();

        Event::assertNotDispatched(AnomalyDetected::class);
    }

    public function test_old_records_are_outside_the_window(): void
    {
        Event::fake([AnomalyDetected::class]);

        foreach (range(1, 4) as $i) {
            $this->seedChange((string) $i, ChangeType::Updated, Actor::job('App\\Jobs\\Sync', 'burst-job'), '-3 days');
        }

        $this->artisan('audit-log:detect-anomalies', ['--window' => 60])
            ->expectsOutputToContain('No anomalies detected')
            ->assertSuccessful();
    }

    private function seedChange(string $id, ChangeType $event, Actor $actor, string $occurredAt = '-5 minutes'): void
    {
        $this->app->make(AuditRecordRepository::class)->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', $id),
            event: $event,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: $actor,
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable($occurredAt),
        ));
    }
}
