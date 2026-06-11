<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\TestCase;

final class SettingsTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('audit-log.ui.middleware', ['web']);
        $app['config']->set('database.connections.audit_target', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function test_the_transfer_button_moves_rows_to_the_target(): void
    {
        $this->app->make(AuditRecordRepository::class)->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 1),
            event: ChangeType::Created,
            diff: Diff::between([], ['status' => 'new']),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        ));

        $response = $this->post('audit-log/settings/database/transfer', [
            'from' => 'testing',
            'to' => 'audit_target',
        ]);

        $response->assertRedirect(route('audit-log.settings'));
        $response->assertSessionHas('audit_log_status');

        $this->assertSame(1, DB::connection('audit_target')->table('audit_log')->count());
    }

    public function test_a_transfer_to_the_same_connection_is_rejected(): void
    {
        $this->from('audit-log/settings')
            ->post('audit-log/settings/database/transfer', ['from' => 'testing', 'to' => 'testing'])
            ->assertSessionHasErrors('to');
    }
}
