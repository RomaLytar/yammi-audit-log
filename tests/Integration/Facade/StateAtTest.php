<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Facade;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Exception\InvalidAuditData;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Tests\TestCase;

final class StateAtTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_facade_reconstructs_the_state_at_a_date(): void
    {
        $this->seedHistory();

        $state = AuditLog::stateAt('App\\Models\\Order', '42', '2026-01-15');

        $this->assertTrue($state->existed);
        $this->assertSame('fresh-status', $state->attributes['status']);
        $this->assertSame(100, $state->attributes['total']);
        $this->assertSame(1, $state->appliedChanges);
        $this->assertStringContainsString('23:59:59', $state->at);
    }

    public function test_without_a_moment_the_state_is_now(): void
    {
        $this->seedHistory();

        $state = AuditLog::stateAt('App\\Models\\Order', '42');

        $this->assertSame('paid-status', $state->attributes['status']);
        $this->assertSame(2, $state->appliedChanges);
    }

    public function test_an_unparseable_date_is_rejected(): void
    {
        $this->expectException(InvalidAuditData::class);

        AuditLog::stateAt('App\\Models\\Order', '42', 'not-a-date');
    }

    public function test_the_playground_executes_state_at(): void
    {
        $this->seedHistory();

        $response = $this->postJson(route('audit-log.playground.execute'), [
            'method' => 'stateAt',
            'args' => [
                'auditable_type' => 'App\\Models\\Order',
                'auditable_id' => '42',
                'at' => '2026-01-15',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('result.existed', true);
        $response->assertJsonPath('result.attributes.status', 'fresh-status');
    }

    public function test_the_playground_page_lists_state_at(): void
    {
        $this->get('audit-log/settings/playground')
            ->assertOk()
            ->assertSee('AuditLog::stateAt');
    }

    private function seedHistory(): void
    {
        $repository = $this->app->make(AuditRecordRepository::class);

        $repository->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', '42'),
            event: ChangeType::Created,
            diff: Diff::between([], ['status' => 'fresh-status', 'total' => 100]),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01 10:00:00'),
        ));

        $repository->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', '42'),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'fresh-status'], ['status' => 'paid-status']),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-02-01 10:00:00'),
        ));
    }
}
