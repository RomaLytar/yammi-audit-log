<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Persistence;

use DateTimeImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class ChangedKeysTest extends TestCase
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

    public function test_a_write_records_the_names_of_the_changed_fields(): void
    {
        $post = Post::create(['title' => 'A', 'status' => 'pending']);
        $post->update(['status' => 'paid']);

        $updateId = DB::table('audit_log')->where('event', 'updated')->value('id');

        $this->assertDatabaseHas('audit_log_changed_keys', ['audit_id' => $updateId, 'key' => 'status']);
        $this->assertDatabaseMissing('audit_log_changed_keys', ['audit_id' => $updateId, 'key' => 'title']);
    }

    public function test_the_field_filter_resolves_through_the_changed_keys_table(): void
    {
        $post = Post::create(['title' => 'A', 'status' => 'pending']);
        $post->update(['status' => 'paid']);

        $expected = AuditLog::query()->field('status')->get()->total;
        $this->assertGreaterThan(0, $expected);

        DB::table('audit_log_changed_keys')->delete();

        $this->assertSame(0, AuditLog::query()->field('status')->get()->total);

        $this->artisan('audit-log:backfill-changed-keys')->assertSuccessful();

        $this->assertSame($expected, AuditLog::query()->field('status')->get()->total);
    }

    public function test_the_backfill_is_idempotent(): void
    {
        $post = Post::create(['title' => 'A', 'status' => 'pending']);
        $post->update(['status' => 'paid']);

        $before = DB::table('audit_log_changed_keys')->count();

        $this->artisan('audit-log:backfill-changed-keys')->assertSuccessful();

        $this->assertSame($before, DB::table('audit_log_changed_keys')->count());
    }

    public function test_pruning_records_removes_their_changed_keys(): void
    {
        $post = Post::create(['title' => 'A', 'status' => 'pending']);
        $post->update(['status' => 'paid']);

        DB::table('audit_log')->update(['occurred_at' => '2000-01-01 00:00:00']);

        $this->assertGreaterThan(0, DB::table('audit_log_changed_keys')->count());

        $this->app->make(AuditRecordRepository::class)
            ->deleteOlderThan(new DateTimeImmutable('2001-01-01'));

        $this->assertSame(0, DB::table('audit_log_changed_keys')->count());
    }
}
