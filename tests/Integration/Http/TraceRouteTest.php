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
        // The chain shows the field-level old to new detail.
        $response->assertSee('status');
        $response->assertSee('draft');
        $response->assertSee('published');
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
