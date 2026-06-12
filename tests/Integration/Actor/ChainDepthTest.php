<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Actor;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Jobs\OuterJob;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class ChainDepthTest extends TestCase
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

    public function test_nested_jobs_record_increasing_depth(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);

        OuterJob::dispatchSync($post->getKey());

        $depths = AuditRecordModel::query()->orderBy('id')->pluck('chain_depth')->all();

        $this->assertSame([0, 1, 2], array_map(intval(...), $depths));
    }
}
