<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Integrity;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditChainStateModel;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class ChainStateTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('audit-log.integrity.enabled', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    public function test_the_chain_state_row_is_seeded_on_an_empty_table(): void
    {
        $state = AuditChainStateModel::query()->findOrFail(AuditChainStateModel::ROW_ID);

        $this->assertNull($state->getAttribute('last_hash'));
    }

    public function test_the_first_insert_is_hashed_and_tracked_by_the_state_row(): void
    {
        Post::create(['title' => 'A', 'status' => 'draft']);

        $head = AuditRecordModel::query()->orderByDesc('id')->firstOrFail();
        $state = AuditChainStateModel::query()->findOrFail(AuditChainStateModel::ROW_ID);

        $this->assertNotNull($head->getAttribute('integrity_hash'));
        $this->assertSame($head->getAttribute('integrity_hash'), $state->getAttribute('last_hash'));
    }

    public function test_consecutive_inserts_form_a_continuous_chain(): void
    {
        $post = Post::create(['title' => 'A', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $hashes = AuditRecordModel::query()->orderBy('id')->pluck('integrity_hash')->all();

        $this->assertCount(2, $hashes);
        $this->assertNotNull($hashes[0]);
        $this->assertNotNull($hashes[1]);
        $this->assertNotSame($hashes[0], $hashes[1]);

        $state = AuditChainStateModel::query()->findOrFail(AuditChainStateModel::ROW_ID);
        $this->assertSame($hashes[1], $state->getAttribute('last_hash'));
    }
}
