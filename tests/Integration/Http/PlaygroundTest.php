<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class PlaygroundTest extends TestCase
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

    public function test_the_page_documents_every_facade_method_with_examples(): void
    {
        $response = $this->get('audit-log/settings/playground');

        $response->assertOk();
        $response->assertSee('Facade playground');
        $response->assertSee('AuditLog::for()');
        $response->assertSee('AuditLog::record()');
        $response->assertSee('read-only');
        $response->assertSee('writes data');
        $response->assertSee('AuditLog::for(Order::class, 42)');
        $response->assertSee('mass', false);
    }

    public function test_the_settings_page_links_to_the_playground(): void
    {
        $this->get('audit-log/settings')->assertSee('settings/playground');
    }

    public function test_executing_for_returns_the_timeline(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $response = $this->postJson(route('audit-log.playground.execute'), [
            'method' => 'for',
            'args' => [
                'auditable_type' => $post->getMorphClass(),
                'auditable_id' => (string) $post->getKey(),
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('result.entries.0.event', 'updated');
    }

    public function test_executing_record_writes_an_audit_row(): void
    {
        $response = $this->postJson(route('audit-log.playground.execute'), [
            'method' => 'record',
            'args' => [
                'auditable_type' => 'App\\Models\\Order',
                'auditable_id' => '42',
                'event' => 'updated',
                'before' => '{"status": "pending"}',
                'after' => '{"status": "cancelled"}',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);

        $this->assertSame(1, AuditRecordModel::query()->count());
    }

    public function test_an_unknown_method_is_rejected(): void
    {
        $this->postJson(route('audit-log.playground.execute'), ['method' => 'dropTables'])
            ->assertNotFound();
    }

    public function test_invalid_json_arguments_return_a_validation_error(): void
    {
        $response = $this->postJson(route('audit-log.playground.execute'), [
            'method' => 'record',
            'args' => [
                'auditable_type' => 'App\\Models\\Order',
                'auditable_id' => '42',
                'event' => 'updated',
                'before' => 'not json',
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('ok', false);
    }
}
