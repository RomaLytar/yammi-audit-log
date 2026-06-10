<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Correlation;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Jobs\PublishPostJob;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class CorrelationCascadeTest extends TestCase
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

    public function test_changes_in_one_unit_of_work_share_a_correlation(): void
    {
        $correlation = $this->app->make(CorrelationContext::class);

        $correlation->push('trace-unit');
        $a = Post::create(['title' => 'A', 'status' => 'draft']);
        Post::create(['title' => 'B', 'status' => 'draft']);
        $a->update(['status' => 'published']);
        $correlation->pop();

        // Outside the unit of work, there is no correlation.
        Post::create(['title' => 'C', 'status' => 'draft']);

        $this->assertSame(3, AuditRecordModel::query()->where('correlation_id', 'trace-unit')->count());
        $this->assertSame(1, AuditRecordModel::query()->whereNull('correlation_id')->count());
    }

    public function test_a_job_dispatched_within_the_unit_keeps_the_same_correlation(): void
    {
        $correlation = $this->app->make(CorrelationContext::class);

        $correlation->push('trace-cascade');
        $post = Post::create(['title' => 'Order', 'status' => 'draft']);
        PublishPostJob::dispatchSync($post->getKey());
        $correlation->pop();

        // The create and the job-driven update share the one correlation.
        $this->assertSame(2, AuditRecordModel::query()->where('correlation_id', 'trace-cascade')->count());
    }
}
