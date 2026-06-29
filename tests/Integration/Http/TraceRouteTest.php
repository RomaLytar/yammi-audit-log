<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Jobs\PublishPostJob;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class TraceRouteTest extends TestCase
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

    private const CORRELATION = '0d4007cb-44a3-4af1-a807-5acc4ec5d3a1';

    public function test_it_renders_a_change_chain(): void
    {
        $correlation = $this->app->make(CorrelationContext::class);
        $correlation->push(self::CORRELATION);
        $post = Post::create(['title' => 'Order', 'status' => 'draft']);
        PublishPostJob::dispatchSync($post->getKey());
        $correlation->pop();

        $response = $this->get('audit-log/trace/'.self::CORRELATION);

        $response->assertOk();
        $response->assertSee('Change chain');
        $response->assertSee('Root');
        $response->assertSee('Post');
        // The job that ran inside the chain is shown as its own span node.
        $response->assertSee('Queued job');
        // The chain shows the field-level old to new detail.
        $response->assertSee('status');
        $response->assertSee('draft');
        $response->assertSee('published');
    }

    public function test_the_entry_you_navigated_from_is_highlighted(): void
    {
        $correlation = $this->app->make(CorrelationContext::class);
        $correlation->push(self::CORRELATION);
        $post = Post::create(['title' => 'Order', 'status' => 'draft']);
        PublishPostJob::dispatchSync($post->getKey());
        $correlation->pop();

        $recordId = (int) AuditRecordModel::query()->min('id');

        $this->get('audit-log/trace/'.self::CORRELATION.'?entry='.$recordId)
            ->assertOk()
            ->assertSee('You came from here')
            ->assertSee('al-focus-entry');

        $this->get('audit-log/trace/'.self::CORRELATION)
            ->assertOk()
            ->assertDontSee('You came from here');
    }

    public function test_diffs_are_collapsed_into_field_summaries(): void
    {
        $correlation = $this->app->make(CorrelationContext::class);
        $correlation->push(self::CORRELATION);
        $post = Post::create(['title' => 'Order', 'status' => 'draft']);
        PublishPostJob::dispatchSync($post->getKey());
        $correlation->pop();

        $response = $this->get('audit-log/trace/'.self::CORRELATION);

        $response->assertOk();
        $response->assertSee('al-trace-diff-');
        $response->assertSee('Click an entry to see its field-level changes');
        $response->assertSee('Expand all');
        $response->assertSee('__alTraceToggleAll', false);
    }

    public function test_an_unknown_correlation_returns_404(): void
    {
        $this->get('audit-log/trace/be7cd1b6-0e4c-4c45-ae63-4f5048ec4dcf')->assertNotFound();
    }

    public function test_a_malformed_correlation_is_rejected_by_the_route(): void
    {
        $this->get('audit-log/trace/not-a-uuid')->assertNotFound();
        $this->get('audit-log/trace/1%20OR%201=1')->assertNotFound();
    }
}
