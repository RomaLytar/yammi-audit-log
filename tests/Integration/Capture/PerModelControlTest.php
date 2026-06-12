<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Capture;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Document;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class PerModelControlTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.capture.mode', 'opt_in');
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });

        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('internal_notes')->nullable();
        });
    }

    public function test_opt_in_mode_captures_only_marked_models(): void
    {
        Post::create(['title' => 'Hello', 'status' => 'draft']);
        Document::create(['title' => 'Spec', 'internal_notes' => 'secret plan']);

        $records = AuditRecordModel::query()->get();

        $this->assertCount(1, $records);
        $this->assertStringContainsString('Document', (string) $records[0]->getAttribute('auditable_type'));
    }

    public function test_excluded_attributes_never_reach_the_stored_diff(): void
    {
        Document::create(['title' => 'Spec', 'internal_notes' => 'secret plan']);

        $changes = (string) json_encode(AuditRecordModel::query()->firstOrFail()->getAttribute('changes'));

        $this->assertStringContainsString('Spec', $changes);
        $this->assertStringNotContainsString('secret plan', $changes);
    }
}
