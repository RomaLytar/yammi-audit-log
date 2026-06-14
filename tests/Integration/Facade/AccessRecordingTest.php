<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Facade;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Article;
use Yammi\AuditLog\Tests\Support\Models\User;
use Yammi\AuditLog\Tests\TestCase;

final class AccessRecordingTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
        });
    }

    public function test_record_access_via_facade_records_an_accessed_event_with_no_diff(): void
    {
        $entry = AuditLog::recordAccess('App\\Models\\Order', 42);

        $this->assertNotNull($entry);
        $this->assertSame('accessed', $entry->event);
        $this->assertSame('App\\Models\\Order', $entry->auditableType);
        $this->assertSame('42', $entry->auditableId);
        $this->assertSame([], $entry->changes);
        $this->assertSame(1, AuditRecordModel::query()->count());
    }

    public function test_access_is_attributed_to_the_viewer(): void
    {
        $this->actingAs(new User(['id' => 3, 'name' => 'Jane Doe']));

        $entry = AuditLog::recordAccess('App\\Models\\Order', 1);

        $this->assertNotNull($entry);
        $this->assertSame(ActorType::User->value, $entry->actorType);
        $this->assertSame('Jane Doe', $entry->actorLabel);
    }

    public function test_the_trait_records_access_for_the_model(): void
    {
        $article = Article::create(['title' => 'Hello']);
        AuditRecordModel::query()->delete();

        $entry = $article->recordAccess();

        $this->assertNotNull($entry);
        $this->assertSame('accessed', $entry->event);
        $this->assertSame($article->getMorphClass(), $entry->auditableType);
        $this->assertSame((string) $article->id, $entry->auditableId);
    }

    public function test_access_events_are_filterable_in_the_changes_feed(): void
    {
        AuditLog::recordAccess('App\\Models\\Order', 7);

        $list = AuditLog::changes(['event' => 'accessed']);

        $this->assertSame(1, $list->total);
        $this->assertSame('accessed', $list->entries[0]->event);
    }
}
