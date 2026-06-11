<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Console;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\TestCase;

final class TransferAuditDataCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.connections.audit_target', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function test_it_moves_rows_to_the_target_connection(): void
    {
        $this->seedRecords(3);

        $this->artisan('audit-log:transfer-data', ['--to' => 'audit_target'])
            ->assertSuccessful();

        $this->assertSame(3, DB::connection('audit_target')->table('audit_log')->count());
        $this->assertSame(3, DB::table('audit_log')->count());
    }

    public function test_delete_source_drops_the_source_table(): void
    {
        $this->seedRecords(2);

        $this->artisan('audit-log:transfer-data', ['--to' => 'audit_target', '--delete-source' => true])
            ->assertSuccessful();

        $this->assertSame(2, DB::connection('audit_target')->table('audit_log')->count());
        $this->assertFalse(Schema::hasTable('audit_log'));
    }

    public function test_it_fails_without_a_target_connection(): void
    {
        $this->artisan('audit-log:transfer-data')->assertFailed();
    }

    public function test_it_fails_when_source_equals_target(): void
    {
        $this->artisan('audit-log:transfer-data', ['--to' => 'testing'])->assertFailed();
    }

    public function test_the_configured_connection_is_the_default_target(): void
    {
        $this->seedRecords(1);

        $this->app['config']->set('audit-log.database.connection', 'audit_target');

        $this->artisan('audit-log:transfer-data', ['--from' => 'testing'])
            ->assertSuccessful();

        $this->assertSame(1, DB::connection('audit_target')->table('audit_log')->count());
    }

    private function seedRecords(int $count): void
    {
        $repository = $this->app->make(AuditRecordRepository::class);

        foreach (range(1, $count) as $i) {
            $repository->save(new AuditRecord(
                auditable: AuditableReference::to('App\\Models\\Order', $i),
                event: ChangeType::Created,
                diff: Diff::between([], ['status' => 'new']),
                actor: Actor::system(),
                origin: null,
                labels: LabelSnapshot::empty(),
                occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            ));
        }
    }
}
