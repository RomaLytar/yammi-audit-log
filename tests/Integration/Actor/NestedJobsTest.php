<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Actor;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Tests\Support\Jobs\OuterJob;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class NestedJobsTest extends TestCase
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

    public function test_a_nested_job_records_its_immediate_parent_and_shares_the_chain(): void
    {
        $post = Post::create(['title' => 'T', 'status' => 'new']);

        OuterJob::dispatchSync($post->getKey());

        $timeline = $this->app->make(AuditRecordRepository::class)->timelineFor(
            AuditableReference::to($post->getMorphClass(), (string) $post->getKey()),
        );

        // Newest first: inner update, outer update, create.
        $inner = $timeline[0];
        $outer = $timeline[1];

        $this->assertSame(ActorType::Job, $inner->actor()->type);
        $this->assertStringContainsString('InnerJob', $inner->actor()->displayLabel());

        // The inner job's origin is the outer job that spawned it.
        $this->assertStringContainsString('OuterJob', (string) $inner->origin()?->displayLabel());

        // Both job steps belong to the same chain.
        $this->assertNotNull($inner->correlationId());
        $this->assertSame($outer->correlationId(), $inner->correlationId());
    }
}
