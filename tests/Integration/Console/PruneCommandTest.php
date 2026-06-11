<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Console;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\TestCase;

final class PruneCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.retention.days', 30);
        $app['config']->set('audit-log.retention.schedule.enabled', false);
    }

    public function test_it_prunes_records_older_than_the_retention_window(): void
    {
        $repository = $this->app->make(AuditRecordRepository::class);
        $repository->save($this->record(new DateTimeImmutable('2020-01-01T10:00:00+00:00')));
        $repository->save($this->record(new DateTimeImmutable));

        $this->artisan('audit-log:prune')->assertSuccessful();

        $this->assertSame(1, AuditRecordModel::query()->count());
    }

    public function test_the_days_option_overrides_the_configured_retention(): void
    {
        $repository = $this->app->make(AuditRecordRepository::class);
        $repository->save($this->record((new DateTimeImmutable)->modify('-50 days')));
        $repository->save($this->record(new DateTimeImmutable));

        $this->artisan('audit-log:prune', ['--days' => 60])->assertSuccessful();
        $this->assertSame(2, AuditRecordModel::query()->count());

        $this->artisan('audit-log:prune', ['--days' => 10])->assertSuccessful();
        $this->assertSame(1, AuditRecordModel::query()->count());
    }

    public function test_a_zero_days_override_prunes_nothing(): void
    {
        $repository = $this->app->make(AuditRecordRepository::class);
        $repository->save($this->record(new DateTimeImmutable('2020-01-01T10:00:00+00:00')));

        $this->artisan('audit-log:prune', ['--days' => 0])->assertSuccessful();

        $this->assertSame(1, AuditRecordModel::query()->count());
    }

    private function record(DateTimeImmutable $occurredAt): AuditRecord
    {
        return new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 1),
            event: ChangeType::Created,
            diff: Diff::empty(),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: $occurredAt,
        );
    }
}
