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

    public function test_state_endpoint_reconstructs_the_record(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $this->getJson('audit-log/api/state?auditable_type='.urlencode($post->getMorphClass()).'&auditable_id='.$post->getKey())
            ->assertOk()
            ->assertJsonPath('data.existed', true)
            ->assertJsonPath('data.attributes.status', 'published');

        $this->getJson('audit-log/api/state')->assertStatus(422);
    }

    public function test_record_view_endpoint_returns_history_and_related(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);

        $this->getJson('audit-log/api/record-view?auditable_type='.urlencode($post->getMorphClass()).'&auditable_id='.$post->getKey())
            ->assertOk()
            ->assertJsonPath('data.auditableId', (string) $post->getKey())
            ->assertJsonPath('data.entries.0.event', 'created');
    }

    public function test_subject_report_endpoint_returns_both_sections(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);

        $this->getJson('audit-log/api/subject-report?auditable_type='.urlencode($post->getMorphClass()).'&auditable_id='.$post->getKey())
            ->assertOk()
            ->assertJsonPath('data.auditableId', (string) $post->getKey());
    }

    public function test_anomalies_endpoint_returns_findings(): void
    {
        $this->app['config']->set('audit-log.anomalies.rate_threshold', 1);

        Post::create(['title' => 'One', 'status' => 'draft']);
        Post::create(['title' => 'Two', 'status' => 'draft']);

        $response = $this->getJson('audit-log/api/anomalies?window=1440');

        $response->assertOk();
        $response->assertJsonPath('data.0.rule', 'rate_spike');

        $this->getJson('audit-log/api/anomalies?window=0')->assertStatus(422);
    }
}
