<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class VerifyIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.integrity.enabled', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    public function test_records_are_chained_and_the_chain_verifies(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);
        $post->delete();

        $hashes = AuditRecordModel::query()->orderBy('id')->pluck('integrity_hash');

        $this->assertCount(3, $hashes);
        $this->assertCount(3, array_unique($hashes->all()));

        foreach ($hashes as $hash) {
            $this->assertIsString($hash);
            $this->assertSame(64, strlen($hash));
        }

        $this->artisan('audit-log:verify')
            ->expectsOutputToContain('Integrity OK: 3 hashed record(s) verified')
            ->assertSuccessful();
    }

    public function test_a_tampered_record_breaks_verification(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        AuditRecordModel::query()->orderBy('id')->firstOrFail()
            ->forceFill(['changes' => ['title' => ['old' => null, 'new' => 'FORGED']]])
            ->saveQuietly();

        $this->artisan('audit-log:verify')->assertFailed();
    }

    public function test_pruning_keeps_the_chain_verifiable(): void
    {
        $repository = $this->app->make(AuditRecordRepository::class);

        foreach (['2020-01-01', '2020-06-01', '2026-06-01'] as $i => $date) {
            $repository->save(new AuditRecord(
                auditable: AuditableReference::to('App\\Models\\Order', $i + 1),
                event: ChangeType::Created,
                diff: Diff::between([], ['status' => 'new']),
                actor: Actor::system(),
                origin: null,
                labels: LabelSnapshot::empty(),
                occurredAt: new \DateTimeImmutable($date.'T10:00:00+00:00'),
            ));
        }

        $repository->deleteOlderThan(new \DateTimeImmutable('2021-01-01T00:00:00+00:00'));

        $this->artisan('audit-log:verify')->assertSuccessful();
    }

    public function test_records_written_without_hashing_are_reported_not_failed(): void
    {
        $this->app['config']->set('audit-log.integrity.enabled', false);

        Post::create(['title' => 'Hello', 'status' => 'draft']);

        $this->artisan('audit-log:verify')
            ->expectsOutputToContain('1 recorded without hashing')
            ->assertSuccessful();
    }
}
