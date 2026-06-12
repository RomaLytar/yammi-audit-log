<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Alert;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class AlertChannelsTest extends TestCase
{
    use RefreshDatabase;

    private const SLACK_URL = 'https://hooks.slack.test/services/T0/B0/x';

    private const WEBHOOK_URL = 'https://hub.example.test/audit';

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.alerts.rules', [
            ['model' => Post::class, 'attributes' => ['status'], 'events' => ['updated']],
        ]);
        $app['config']->set('audit-log.alerts.slack_webhook_url', self::SLACK_URL);
        $app['config']->set('audit-log.alerts.webhook.url', self::WEBHOOK_URL);
        $app['config']->set('audit-log.alerts.webhook.secret', 'shared-secret');
        $app['config']->set('audit-log.anomalies.rate_threshold', 2);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    public function test_a_matching_change_posts_to_slack_and_the_webhook(): void
    {
        Http::fake();

        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        Http::assertSent(function (ClientRequest $request): bool {
            if ($request->url() !== self::SLACK_URL) {
                return false;
            }

            $body = $request->data();

            return isset($body['blocks'])
                && str_contains((string) json_encode($body), 'Updated Post #1')
                && str_contains((string) json_encode($body), 'status');
        });

        Http::assertSent(function (ClientRequest $request): bool {
            if ($request->url() !== self::WEBHOOK_URL) {
                return false;
            }

            $raw = $request->body();
            $decoded = json_decode($raw, true);

            return is_array($decoded)
                && $decoded['event'] === 'audit.sensitive_change'
                && $decoded['context']['id'] === '1'
                && str_contains((string) ($decoded['deep_link'] ?? ''), 'id=1')
                && $request->header('X-Audit-Log-Signature')[0] === 'sha256='.hash_hmac('sha256', $raw, 'shared-secret');
        });
    }

    public function test_a_non_matching_change_sends_nothing(): void
    {
        Http::fake();

        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['title' => 'Renamed']);

        Http::assertNothingSent();
    }

    public function test_a_dead_channel_never_breaks_the_write(): void
    {
        Http::fake([
            self::SLACK_URL => Http::response('no', 500),
            self::WEBHOOK_URL => Http::response('ok', 200),
        ]);

        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $this->assertSame('published', $post->fresh()?->getAttribute('status'));

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === self::WEBHOOK_URL);
    }

    public function test_anomaly_findings_reach_the_channels_as_one_summary(): void
    {
        Http::fake();

        Post::create(['title' => 'One', 'status' => 'draft']);
        Post::create(['title' => 'Two', 'status' => 'draft']);
        Post::create(['title' => 'Three', 'status' => 'draft']);

        $this->artisan('audit-log:detect-anomalies')->assertSuccessful();

        Http::assertSent(function (ClientRequest $request): bool {
            if ($request->url() !== self::WEBHOOK_URL) {
                return false;
            }

            $decoded = json_decode($request->body(), true);

            return is_array($decoded)
                && $decoded['event'] === 'audit.anomaly'
                && str_contains((string) json_encode($decoded['lines']), 'rate_spike');
        });
    }

    public function test_a_5xx_webhook_is_retried_once(): void
    {
        Http::fakeSequence(self::WEBHOOK_URL)->pushStatus(503)->pushStatus(200);
        Http::fake([self::SLACK_URL => Http::response('ok', 200)]);

        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $sent = 0;
        Http::assertSent(function (ClientRequest $request) use (&$sent): bool {
            if ($request->url() === self::WEBHOOK_URL) {
                $sent++;
            }

            return true;
        });

        $this->assertSame(2, $sent);
    }
}
