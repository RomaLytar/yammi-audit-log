<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Yammi\AuditLog\Infrastructure\Capture\CaptureFailureLog;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class StatsRouteTest extends TestCase
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

    public function test_the_stats_page_renders_volume_and_breakdowns(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $response = $this->get('audit-log/stats');

        $response->assertOk();
        $response->assertSee('Statistics');
        $response->assertSee('Total records');
        $response->assertSee('Projected at retention');
        $response->assertSee('By event');
        $response->assertSee('By actor type');
        $response->assertSee('Top models');
        $response->assertSee('Daily activity');
        $response->assertSee('Post');
    }

    public function test_filters_apply_to_the_stats(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $this->get('audit-log/stats?event=created')->assertOk()->assertDontSee('>updated<', false);
    }

    public function test_the_nav_links_to_the_stats_page(): void
    {
        $this->get('audit-log')->assertSee('audit-log/stats');
    }

    public function test_the_stats_page_warns_about_capture_failures(): void
    {
        $this->app->make(CaptureFailureLog::class)->record(null, null, new RuntimeException('boom in capture'));

        $response = $this->get('audit-log/stats');

        $response->assertOk();
        $response->assertSee('capture failure');
        $response->assertSee('boom in capture');
    }
}
