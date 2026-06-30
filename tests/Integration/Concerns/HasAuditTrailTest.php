<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Application\DTO\Audit\TimelineData;
use Yammi\AuditLog\Tests\Support\Models\AuditedArticle;
use Yammi\AuditLog\Tests\TestCase;

final class HasAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
        });
    }

    public function test_audit_trail_returns_the_models_own_timeline(): void
    {
        $article = AuditedArticle::create(['title' => 'A', 'status' => 'draft']);
        $article->update(['status' => 'published']);

        $trail = $article->auditTrail();

        $this->assertInstanceOf(TimelineData::class, $trail);
        $this->assertSame(2, $trail->count());
        $this->assertSame(AuditedArticle::class, $trail->auditableType);
        $this->assertSame((string) $article->getKey(), $trail->auditableId);
        $this->assertSame('updated', $trail->entries[0]->event);
    }

    public function test_audit_state_at_reconstructs_the_models_state(): void
    {
        $article = AuditedArticle::create(['title' => 'A', 'status' => 'draft']);
        $article->update(['status' => 'published']);

        $state = $article->auditStateAt();

        $this->assertTrue($state->existed);
        $this->assertSame('published', $state->attributes['status'] ?? null);
    }
}
