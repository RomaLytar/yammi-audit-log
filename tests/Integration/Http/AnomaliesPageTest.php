<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Tests\TestCase;

final class AnomaliesPageTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.anomalies.rate_threshold', 3);
        $app['config']->set('audit-log.anomalies.delete_threshold', 0);
        $app['config']->set('audit-log.anomalies.off_hours', []);
    }

    public function test_the_nav_links_to_the_anomalies_page(): void
    {
        $this->get('audit-log')
            ->assertOk()
            ->assertSee('audit-log/anomalies');
    }

    public function test_a_quiet_log_shows_the_all_clear_state(): void
    {
        $this->get('audit-log/anomalies')
            ->assertOk()
            ->assertSee('No anomalies in the last 24 hours')
            ->assertSee('Change burst: more than 3 changes by one actor')
            ->assertSee('Mass delete: off')
            ->assertSee('Off-hours: off');
    }

    public function test_findings_are_listed_with_their_rule_badges(): void
    {
        foreach (range(1, 4) as $i) {
            $this->seedChange((string) $i);
        }

        $this->get('audit-log/anomalies')
            ->assertOk()
            ->assertSee('1 finding')
            ->assertSee('Change burst')
            ->assertSee('Severity')
            ->assertSee('high')
            ->assertSee('burst-job')
            ->assertSee('Tune thresholds');
    }

    public function test_an_unknown_window_falls_back_to_24_hours(): void
    {
        $this->get('audit-log/anomalies?window=12345')
            ->assertOk()
            ->assertSee('No anomalies in the last 24 hours');
    }

    public function test_the_window_can_be_widened(): void
    {
        foreach (range(1, 4) as $i) {
            $this->seedChange((string) $i, '-3 days');
        }

        $this->get('audit-log/anomalies?window=1440')
            ->assertOk()
            ->assertSee('No anomalies');

        $this->get('audit-log/anomalies?window=10080')
            ->assertOk()
            ->assertSee('Change burst');
    }

    public function test_the_facade_and_playground_expose_the_scan(): void
    {
        foreach (range(1, 4) as $i) {
            $this->seedChange((string) $i);
        }

        $findings = AuditLog::anomalies(1440);

        $this->assertNotSame([], $findings);
        $this->assertSame('rate_spike', $findings[0]->rule);

        $response = $this->postJson(route('audit-log.playground.execute'), [
            'method' => 'anomalies',
            'args' => ['window' => 1440],
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('result.0.rule', 'rate_spike');
    }

    public function test_the_settings_page_carries_the_anomaly_group(): void
    {
        $this->get('audit-log/settings/general')
            ->assertOk()
            ->assertSee('Anomaly detection')
            ->assertSee('anomalies_rate_threshold')
            ->assertSee('section-anomaly-detection');
    }

    private function seedChange(string $id, string $occurredAt = '-5 minutes'): void
    {
        $this->app->make(AuditRecordRepository::class)->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', $id),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::job('App\\Jobs\\Sync', 'burst-job'),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable($occurredAt),
        ));
    }
}
