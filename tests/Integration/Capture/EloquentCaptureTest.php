<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Capture;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class EloquentCaptureTest extends TestCase
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

    public function test_it_audits_create_update_and_delete_for_any_model(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);
        $post->delete();

        $repository = $this->app->make(AuditRecordRepository::class);
        $timeline = $repository->timelineFor(
            AuditableReference::to($post->getMorphClass(), (string) $post->getKey()),
        );

        $this->assertCount(3, $timeline);

        $this->assertSame(ChangeType::Deleted, $timeline[0]->event());
        $this->assertSame(ChangeType::Updated, $timeline[1]->event());
        $this->assertSame(ChangeType::Created, $timeline[2]->event());

        $statusChange = $timeline[1]->diff()->field('status');
        $this->assertSame('draft', $statusChange?->old);
        $this->assertSame('published', $statusChange?->new);

        $this->assertSame('System', $timeline[0]->actor()->displayLabel());
    }

    public function test_it_does_not_audit_its_own_records(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);

        $repository = $this->app->make(AuditRecordRepository::class);
        $reference = AuditableReference::to($post->getMorphClass(), (string) $post->getKey());

        $this->assertCount(1, $repository->timelineFor($reference));
    }
}
