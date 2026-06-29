<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Correlation;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\ValueObject\Span;
use Yammi\AuditLog\Infrastructure\Correlation\SpanContext;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Jobs\PublishPostJob;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class SpanCascadeTest extends TestCase
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

    public function test_changes_in_one_unit_of_work_share_a_span(): void
    {
        $spans = $this->app->make(SpanContext::class);

        $spans->push(new Span('root-span'));
        Post::create(['title' => 'A', 'status' => 'draft']);
        Post::create(['title' => 'B', 'status' => 'draft']);
        $spans->pop();

        // Outside any unit of work, there is no span.
        Post::create(['title' => 'C', 'status' => 'draft']);

        $this->assertSame(2, AuditRecordModel::query()->where('span_id', 'root-span')->count());
        $this->assertSame(1, AuditRecordModel::query()->whereNull('span_id')->count());
    }

    public function test_a_job_dispatched_within_the_unit_links_to_the_parent_span(): void
    {
        $spans = $this->app->make(SpanContext::class);

        $spans->push(new Span('root-span'));
        $post = Post::create(['title' => 'Order', 'status' => 'draft']);
        PublishPostJob::dispatchSync($post->getKey());
        $spans->pop();

        $rootChange = AuditRecordModel::query()->where('span_id', 'root-span')->first();
        $this->assertNotNull($rootChange);
        $this->assertNull($rootChange->getAttribute('parent_span_id'));

        $jobChange = AuditRecordModel::query()
            ->where('parent_span_id', 'root-span')
            ->where('span_id', '!=', 'root-span')
            ->first();

        $this->assertNotNull($jobChange);
        $this->assertNotNull($jobChange->getAttribute('span_id'));
    }
}
