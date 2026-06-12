<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;
use Yammi\AuditLog\Tests\Support\Jobs\PublishPostJob;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class ApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.api.enabled', true);
        $app['config']->set('audit-log.api.middleware', []);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    public function test_changes_endpoint_returns_the_filtered_list(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $response = $this->getJson('audit-log/api/changes?event=updated');

        $response->assertOk();
        $response->assertJsonPath('data.total', 1);
        $response->assertJsonPath('data.entries.0.event', 'updated');
    }

    public function test_chain_endpoint_returns_the_cascade_or_404(): void
    {
        $correlation = $this->app->make(CorrelationContext::class);
        $correlation->push('3d4b7c1a-91a2-4f7e-8d3c-2f64b1a90c11');
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        PublishPostJob::dispatchSync($post->getKey());
        $correlation->pop();

        $this->getJson('audit-log/api/chain/3d4b7c1a-91a2-4f7e-8d3c-2f64b1a90c11')
            ->assertOk()
            ->assertJsonPath('data.modelCount', 1);

        $this->getJson('audit-log/api/chain/00000000-0000-4000-8000-000000000000')->assertNotFound();
    }

    public function test_stats_and_timeline_endpoints_return_data(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);

        $this->getJson('audit-log/api/stats')->assertOk()->assertJsonPath('data.total', 1);

        $this->getJson('audit-log/api/timeline?auditable_type='.urlencode($post->getMorphClass()).'&auditable_id='.$post->getKey())
            ->assertOk()
            ->assertJsonPath('data.entries.0.event', 'created');

        $this->getJson('audit-log/api/timeline')->assertStatus(422);
    }
}
