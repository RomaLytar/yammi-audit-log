<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Integration\Capture;

use DateTimeImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Yammi\AuditLog\Application\Action\Record\RecordChangeAction;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Events\AuditCaptureFailed;
use Yammi\AuditLog\Infrastructure\Capture\CaptureFailureLog;
use Yammi\AuditLog\Infrastructure\Capture\EloquentChangeRecorder;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditCaptureFailureModel;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\Support\ThrowingAuditRecordRepository;
use Yammi\AuditLog\Tests\TestCase;

final class CaptureFailureLogTest extends TestCase
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

    public function test_it_records_lists_counts_and_dispatches_a_failure(): void
    {
        Event::fake([AuditCaptureFailed::class]);

        $post = new Post;
        $post->setRawAttributes(['id' => 1], true);

        $log = $this->app->make(CaptureFailureLog::class);
        $log->record($post, ChangeType::Created, new RuntimeException('boom'));

        $this->assertSame(1, $log->countSince(new DateTimeImmutable('2000-01-01')));

        $recent = $log->recent();
        $this->assertCount(1, $recent);
        $this->assertSame('boom', $recent[0]->message);
        $this->assertSame(RuntimeException::class, $recent[0]->exception);
        $this->assertSame('created', $recent[0]->event);
        $this->assertSame('Post', $recent[0]->model());

        Event::assertDispatched(AuditCaptureFailed::class);
    }

    public function test_recording_is_fail_soft_when_the_table_is_unreachable(): void
    {
        $log = $this->app->make(CaptureFailureLog::class);

        Schema::dropIfExists((new AuditCaptureFailureModel)->getTable());

        $log->record(null, null, new RuntimeException('still fine'));

        $this->expectNotToPerformAssertions();
    }

    public function test_health_is_fail_soft_when_the_table_is_unreachable(): void
    {
        $log = $this->app->make(CaptureFailureLog::class);

        Schema::dropIfExists((new AuditCaptureFailureModel)->getTable());

        $health = $log->health(new DateTimeImmutable('2000-01-01'));

        $this->assertSame(0, $health['count']);
        $this->assertSame([], $health['recent']);
    }

    public function test_recording_is_fail_soft_when_a_listener_throws(): void
    {
        Event::listen(AuditCaptureFailed::class, static function (): void {
            throw new RuntimeException('listener boom');
        });

        $log = $this->app->make(CaptureFailureLog::class);
        $log->record(null, null, new RuntimeException('x'));

        $this->assertSame(1, $log->countSince(new DateTimeImmutable('2000-01-01')));
        $this->assertSame('unknown', $log->recent()[0]->model());
    }

    public function test_a_failed_automatic_capture_is_recorded(): void
    {
        $this->app->instance(AuditRecordRepository::class, new ThrowingAuditRecordRepository);
        $this->app->forgetInstance(RecordChangeAction::class);
        $this->app->forgetInstance(EloquentChangeRecorder::class);

        Post::create(['title' => 'A', 'status' => 'draft']);

        $this->assertSame(
            1,
            $this->app->make(CaptureFailureLog::class)->countSince(new DateTimeImmutable('2000-01-01')),
        );
    }
}
