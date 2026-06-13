<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Transfer\DatabaseTransferRunner;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class IntegrityTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('audit-log.integrity.enabled', true);
        $app['config']->set('audit-log.ui.middleware', ['web']);
        $app['config']->set('database.connections.audit_target', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    public function test_transfer_carries_the_chain_head_so_the_chain_does_not_fork(): void
    {
        $post = Post::create(['title' => 'A', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $sourceHead = DB::connection('testing')->table('audit_log_chain_state')->where('id', 1)->value('last_hash');
        $this->assertIsString($sourceHead);

        $result = $this->app->make(DatabaseTransferRunner::class)->run('testing', 'audit_target', false);
        $this->assertTrue($result->ok);

        $destHead = DB::connection('audit_target')->table('audit_log_chain_state')->where('id', 1)->value('last_hash');

        $this->assertSame($sourceHead, $destHead);
        $this->assertSame(
            DB::connection('audit_target')->table('audit_log')->orderByDesc('id')->value('integrity_hash'),
            $destHead,
        );
    }

    public function test_transfer_moves_signed_digests(): void
    {
        Post::create(['title' => 'A', 'status' => 'draft']);
        $this->artisan('audit-log:digest')->assertSuccessful();
        $this->assertSame(1, DB::connection('testing')->table('audit_log_digests')->count());

        $this->app->make(DatabaseTransferRunner::class)->run('testing', 'audit_target', false);

        $this->assertSame(1, DB::connection('audit_target')->table('audit_log_digests')->count());
    }

    public function test_delete_source_drops_the_chain_state_and_digest_tables(): void
    {
        Post::create(['title' => 'A', 'status' => 'draft']);
        $this->artisan('audit-log:digest')->assertSuccessful();

        $this->app->make(DatabaseTransferRunner::class)->run('testing', 'audit_target', true);

        $source = DB::connection('testing')->getSchemaBuilder();

        $this->assertFalse($source->hasTable('audit_log'));
        $this->assertFalse($source->hasTable('audit_log_digests'));
        $this->assertFalse($source->hasTable('audit_log_chain_state'));
    }
}
