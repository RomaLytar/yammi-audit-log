<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Tests\Support\Jobs\PublishPostJob;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class JobsMonitorBridgeTest extends TestCase
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

    public function test_job_actors_link_to_the_monitor_when_configured(): void
    {
        $this->app['config']->set('audit-log.integrations.jobs_monitor.url', '/jobs-monitor');

        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        PublishPostJob::dispatchSync($post->getKey());

        $response = $this->get('audit-log');

        $response->assertOk();
        $response->assertSee('Open job in JobsMonitor');
        $response->assertSee('/jobs-monitor?search=', false);
    }

    public function test_no_link_is_rendered_without_the_config(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        PublishPostJob::dispatchSync($post->getKey());

        $this->get('audit-log')->assertOk()->assertDontSee('Open job in JobsMonitor');
    }
}
