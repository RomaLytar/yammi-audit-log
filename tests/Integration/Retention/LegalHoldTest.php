<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Retention;

use DateTimeImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Infrastructure\Retention\LegalHoldRegistry;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class LegalHoldTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    public function test_a_held_subject_survives_pruning_while_a_free_one_does_not(): void
    {
        $held = Post::create(['title' => 'Held', 'status' => 'draft']);
        $free = Post::create(['title' => 'Free', 'status' => 'draft']);

        AuditLog::placeLegalHold($held);
        $this->assertNull(AuditLog::legalHolds()[0]->reason);

        $this->prune();

        $this->assertSame(1, $this->recordsFor($held));
        $this->assertSame(0, $this->recordsFor($free));

        $this->assertTrue(AuditLog::releaseLegalHold($held));
        $this->assertFalse(AuditLog::releaseLegalHold($free));

        $this->prune();

        $this->assertSame(0, $this->recordsFor($held));
    }

    public function test_holds_are_listed_and_their_status_is_reported(): void
    {
        $post = Post::create(['title' => 'A', 'status' => 'draft']);

        AuditLog::placeLegalHold($post, null, 'case #4521');

        $registry = $this->app->make(LegalHoldRegistry::class);
        $this->assertTrue($registry->isHeld(Post::class, (string) $post->getKey()));
        $this->assertFalse($registry->isHeld(Post::class, '999'));

        $holds = AuditLog::legalHolds();
        $this->assertCount(1, $holds);
        $this->assertSame('case #4521', $holds[0]->reason);
        $this->assertSame('Post', $holds[0]->model());
        $this->assertSame((string) $post->getKey(), $holds[0]->auditableId);
        $this->assertNotNull($holds[0]->placedAt);

        AuditLog::placeLegalHold('App\\Models\\Widget', 9, 'string subject');
        $this->assertTrue($registry->isHeld('App\\Models\\Widget', '9'));
    }

    public function test_placing_a_hold_is_not_itself_audited(): void
    {
        $post = Post::create(['title' => 'A', 'status' => 'draft']);

        $before = AuditRecordModel::query()->count();
        AuditLog::placeLegalHold($post);

        $this->assertSame($before, AuditRecordModel::query()->count());
    }

    public function test_the_command_places_releases_and_lists_holds(): void
    {
        $registry = $this->app->make(LegalHoldRegistry::class);

        $this->artisan('audit-log:legal-hold', ['action' => 'list'])->assertSuccessful();

        $this->artisan('audit-log:legal-hold', ['action' => 'place', 'model' => 'App\\Models\\Order', 'id' => '5', '--reason' => 'case #1'])
            ->assertSuccessful();
        $this->assertTrue($registry->isHeld('App\\Models\\Order', '5'));

        $this->artisan('audit-log:legal-hold', ['action' => 'list'])->assertSuccessful();

        $this->artisan('audit-log:legal-hold', ['action' => 'release', 'model' => 'App\\Models\\Order', 'id' => '5'])
            ->assertSuccessful();
        $this->assertFalse($registry->isHeld('App\\Models\\Order', '5'));

        $this->artisan('audit-log:legal-hold', ['action' => 'place'])->assertFailed();
        $this->artisan('audit-log:legal-hold', ['action' => 'bogus', 'model' => 'X', 'id' => '1'])->assertFailed();
    }

    private function prune(): void
    {
        $this->app->make(AuditRecordRepository::class)->deleteOlderThan(new DateTimeImmutable('+1 day'));
    }

    private function recordsFor(Post $post): int
    {
        return AuditRecordModel::query()->where('auditable_id', (string) $post->getKey())->count();
    }
}
