<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Facade;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Tests\Support\Jobs\PublishPostJob;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class FacadeReadTest extends TestCase
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

    public function test_changes_returns_the_filtered_dashboard_list(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $all = AuditLog::changes();
        $this->assertSame(2, $all->total);

        $updated = AuditLog::changes(['event' => 'updated']);
        $this->assertSame(1, $updated->total);
        $this->assertSame('updated', $updated->entries[0]->event);

        $searched = AuditLog::changes(['search' => 'published']);
        $this->assertSame(1, $searched->total);

        $this->assertNotSame([], $all->models);
    }

    public function test_unknown_filter_keys_are_ignored(): void
    {
        Post::create(['title' => 'Hello', 'status' => 'draft']);

        $this->assertSame(1, AuditLog::changes(['hack' => "' or 1=1", 'event' => 'bogus'])->total);
    }

    public function test_noise_returns_only_flagged_writes(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->forceFill(['updated_at' => $post->freshTimestamp()->addMinute()])->save();

        $noise = AuditLog::noise();

        $this->assertSame(1, $noise->total);
        $this->assertTrue($noise->entries[0]->isNoise);
    }

    public function test_chain_returns_the_correlated_cascade(): void
    {
        $correlation = $this->app->make(CorrelationContext::class);
        $correlation->push('f8c0a7d2-1234-4cde-9f10-aabbccddeeff');
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        PublishPostJob::dispatchSync($post->getKey());
        $correlation->pop();

        $chain = AuditLog::chain('f8c0a7d2-1234-4cde-9f10-aabbccddeeff');

        $this->assertNotNull($chain);
        $this->assertSame(2, $chain->count());

        $this->assertNull(AuditLog::chain('00000000-0000-4000-8000-000000000000'));
    }

    public function test_stats_returns_volume_and_breakdowns(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $stats = AuditLog::stats();

        $this->assertSame(2, $stats->total);
        $this->assertSame(['created' => 1, 'updated' => 1], $stats->byEvent);
        $this->assertNotNull($stats->projectedRows);

        $this->assertSame(1, AuditLog::stats(['event' => 'created'])->total);
    }
}
