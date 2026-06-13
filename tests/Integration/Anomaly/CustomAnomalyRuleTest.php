<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Anomaly;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Application\DTO\AnomalyData;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Tests\Support\Anomaly\PriceDropRule;
use Yammi\AuditLog\Tests\TestCase;

final class CustomAnomalyRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.anomalies.rules', [PriceDropRule::class]);
    }

    public function test_a_registered_rule_runs_alongside_the_built_in_checks(): void
    {
        $this->save(Diff::between(['price' => 100], ['price' => 50]));
        $this->save(Diff::between(['price' => 100], ['price' => 150]));

        $findings = AuditLog::anomalies(60);

        $priceDrops = array_values(array_filter(
            $findings,
            static fn (AnomalyData $finding): bool => $finding->rule === 'price_drop',
        ));

        $this->assertCount(1, $priceDrops);
        $this->assertSame(AnomalyData::SEVERITY_HIGH, $priceDrops[0]->severity);
    }

    private function save(Diff $diff): void
    {
        $this->app->make(AuditRecordRepository::class)->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Product', 1),
            event: ChangeType::Updated,
            diff: $diff,
            actor: Actor::user('1', 'Jane'),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable,
        ));
    }
}
