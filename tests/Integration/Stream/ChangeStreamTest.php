<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Stream;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class ChangeStreamTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://splunk.test/services/collector/event';

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('queue.default', 'sync');
        $app['config']->set('audit-log.stream.enabled', true);
        $app['config']->set('audit-log.stream.driver', 'splunk');
        $app['config']->set('audit-log.stream.endpoint', self::ENDPOINT);
        $app['config']->set('audit-log.stream.token', 'hec-token');
        $app['config']->set('audit-log.stream.source', 'orders-api');
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    public function test_a_captured_change_is_streamed_to_the_sink(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);

        Post::create(['title' => 'Hello', 'status' => 'draft']);

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->url() === self::ENDPOINT
                && $request->hasHeader('Authorization', 'Splunk hec-token')
                && str_contains($request->body(), '"sourcetype":"audit"')
                && str_contains($request->body(), '"source":"orders-api"')
                && str_contains($request->body(), '"event":');
        });
    }

    public function test_a_manual_record_is_streamed_too(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 200)]);

        AuditLog::record('App\\Models\\Order', 5, 'updated', ['status' => 'a'], ['status' => 'b']);

        Http::assertSent(function (ClientRequest $request): bool {
            return str_contains($request->body(), '"event":')
                && str_contains($request->body(), '"id":"5"');
        });
    }
}
