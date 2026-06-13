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
use Yammi\AuditLog\Tests\TestCase;

final class AnomalyNavBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.anomalies.rate_threshold', 3);
    }

    public function test_the_nav_shows_a_24h_anomaly_count(): void
    {
        foreach (range(1, 4) as $i) {
            $this->seedChange((string) $i);
        }

        $this->get('audit-log')
            ->assertOk()
            ->assertSee('anomaly(ies) in the last 24h', false);
    }

    public function test_no_badge_is_shown_without_anomalies(): void
    {
        $this->seedChange('1');

        $this->get('audit-log')
            ->assertOk()
            ->assertDontSee('anomaly(ies) in the last 24h', false);
    }

    private function seedChange(string $id): void
    {
        $this->app->make(AuditRecordRepository::class)->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', $id),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::user('5', 'Jane Doe'),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('-1 hour'),
        ));
    }
}
