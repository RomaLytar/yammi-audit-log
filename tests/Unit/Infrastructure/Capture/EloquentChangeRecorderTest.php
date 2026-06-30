<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Capture;

use DateTimeImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Mail\Mailer;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\Record\RecordChangeAction;
use Yammi\AuditLog\Application\Pipeline\RecordChangePipeline;
use Yammi\AuditLog\Application\Pipeline\Stage\ComputeDiffStage;
use Yammi\AuditLog\Application\Service\AlertRuleMatcher;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Infrastructure\Alert\AlertDispatcher;
use Yammi\AuditLog\Infrastructure\Capture\AuditableGuard;
use Yammi\AuditLog\Infrastructure\Capture\ChangeDataFactory;
use Yammi\AuditLog\Infrastructure\Capture\EloquentChangeRecorder;
use Yammi\AuditLog\Tests\Support\FixedClock;
use Yammi\AuditLog\Tests\Support\FixedCorrelationResolver;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;
use Yammi\AuditLog\Tests\Support\Models\Post;
use Yammi\AuditLog\Tests\Support\SpyCaptureFailureReporter;
use Yammi\AuditLog\Tests\Support\StripKeysRedactor;
use Yammi\AuditLog\Tests\Support\ThrowingAuditRecordRepository;

final class EloquentChangeRecorderTest extends TestCase
{
    private InMemoryAuditRecordRepository $repository;

    private SpyCaptureFailureReporter $failures;

    protected function setUp(): void
    {
        $this->repository = new InMemoryAuditRecordRepository;
        $this->failures = new SpyCaptureFailureReporter;
    }

    public function test_each_eloquent_verb_maps_to_its_change_type(): void
    {
        $recorder = $this->recorder($this->repository);

        foreach (['created', 'updated', 'deleted', 'restored'] as $verb) {
            $recorder->handle("eloquent.{$verb}: Post", [$this->changedPost()]);
        }

        $events = array_map(
            static fn ($record) => $record->event(),
            $this->repository->saved,
        );

        $this->assertSame(
            [ChangeType::Created, ChangeType::Updated, ChangeType::Deleted, ChangeType::Restored],
            $events,
        );
    }

    public function test_unrelated_eloquent_events_are_ignored(): void
    {
        $recorder = $this->recorder($this->repository);

        $recorder->handle('eloquent.saved: Post', [$this->changedPost()]);
        $recorder->handle('eloquent.booting: Post', [$this->changedPost()]);

        $this->assertSame([], $this->repository->saved);
    }

    public function test_a_payload_without_a_model_is_ignored(): void
    {
        $recorder = $this->recorder($this->repository);

        $recorder->handle('eloquent.created: Post', ['not-a-model']);
        $recorder->handle('eloquent.created: Post', []);

        $this->assertSame([], $this->repository->saved);
    }

    public function test_a_model_rejected_by_the_guard_is_ignored(): void
    {
        $recorder = $this->recorder($this->repository);

        $recorder->handle('eloquent.created: Post', [new Post]);

        $this->assertSame([], $this->repository->saved);
    }

    public function test_a_persistence_failure_is_reported_and_swallowed(): void
    {
        $recorder = $this->recorder(new ThrowingAuditRecordRepository);

        $recorder->handle('eloquent.created: Post', [$this->changedPost()]);

        $this->assertCount(1, $this->failures->reported);
        $this->assertSame(ChangeType::Created, $this->failures->reported[0]['event']);
        $this->assertInstanceOf(Post::class, $this->failures->reported[0]['model']);
    }

    private function recorder(AuditRecordRepository $repository): EloquentChangeRecorder
    {
        $action = new RecordChangeAction(
            new RecordChangePipeline([new ComputeDiffStage(new StripKeysRedactor([]))]),
            $repository,
            new FixedClock(new DateTimeImmutable('2026-01-01T10:00:00+00:00')),
            new FixedCorrelationResolver,
        );

        $alerts = new AlertDispatcher(
            new AlertRuleMatcher,
            $this->createStub(Dispatcher::class),
            $this->createStub(Mailer::class),
        );

        return new EloquentChangeRecorder($action, new ChangeDataFactory, new AuditableGuard([]), $this->failures, $alerts);
    }

    private function changedPost(): Post
    {
        $post = new Post;
        $post->setRawAttributes(['id' => 1, 'status' => 'draft'], true);
        $post->status = 'published';
        $post->syncChanges();

        return $post;
    }
}
