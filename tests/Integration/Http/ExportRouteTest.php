<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Http;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\TestCase;

final class ExportRouteTest extends TestCase
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

    public function test_csv_export_contains_the_filtered_changes(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $response = $this->get('audit-log/export');

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="audit-log.csv"');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('auditable_id', $csv);
        $this->assertStringContainsString('published', $csv);
        $this->assertStringContainsString('created', $csv);
    }

    public function test_csv_export_applies_the_filters(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $csv = $this->get('audit-log/export?event=created')->streamedContent();

        $this->assertStringNotContainsString('updated', $csv);
    }

    public function test_json_export_returns_structured_rows(): void
    {
        $post = Post::create(['title' => 'Hello', 'status' => 'draft']);
        $post->update(['status' => 'published']);

        $response = $this->get('audit-log/export?format=json');

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="audit-log.json"');
        $response->assertJsonPath('count', 2);
        $response->assertJsonPath('data.0.event', 'updated');
        $response->assertJsonPath('data.0.changes.status.new', 'published');
    }

    public function test_the_export_never_sweeps_beyond_the_bounded_range(): void
    {
        $this->app->make(AuditRecordRepository::class)->save(
            new AuditRecord(
                auditable: AuditableReference::to('App\\Models\\Order', 1),
                event: ChangeType::Created,
                diff: Diff::between([], ['status' => 'ancient-history']),
                actor: Actor::system(),
                origin: null,
                labels: LabelSnapshot::empty(),
                occurredAt: new \DateTimeImmutable('-2 years'),
            ),
        );
        Post::create(['title' => 'Hello', 'status' => 'fresh-row']);

        $csv = $this->get('audit-log/export')->streamedContent();

        $this->assertStringContainsString('fresh-row', $csv);
        $this->assertStringNotContainsString('ancient-history', $csv);
    }

    public function test_the_dashboard_links_to_the_export(): void
    {
        Post::create(['title' => 'Hello', 'status' => 'draft']);

        $this->get('audit-log?event=created')
            ->assertOk()
            ->assertSee('audit-log/export', false);
    }
}
