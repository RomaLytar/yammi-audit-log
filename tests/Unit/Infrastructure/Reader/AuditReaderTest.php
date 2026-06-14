<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Reader;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\Read\BuildRecordViewAction;
use Yammi\AuditLog\Application\Action\Read\BuildSubjectReportAction;
use Yammi\AuditLog\Application\Action\Read\BuildTimelineAction;
use Yammi\AuditLog\Application\Action\Read\ReconstructStateAction;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Infrastructure\Reader\AuditReader;
use Yammi\AuditLog\Tests\Support\FixedClock;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;
use Yammi\AuditLog\Tests\Support\Models\Post;

final class AuditReaderTest extends TestCase
{
    private InMemoryAuditRecordRepository $repository;

    private AuditReader $reader;

    protected function setUp(): void
    {
        $this->repository = new InMemoryAuditRecordRepository;
        $clock = new FixedClock(new DateTimeImmutable('2026-06-01T00:00:00+00:00'));

        $this->reader = new AuditReader(
            new BuildTimelineAction($this->repository),
            new ReconstructStateAction($this->repository),
            new BuildSubjectReportAction($this->repository, $clock),
            new BuildRecordViewAction($this->repository, $clock),
        );
    }

    public function test_it_reads_the_timeline_for_a_model_instance(): void
    {
        $post = new Post;
        $post->setRawAttributes(['id' => 7], true);

        $this->repository->save($this->record($post->getMorphClass(), '7'));

        $timeline = $this->reader->for($post);

        $this->assertSame(1, $timeline->count());
        $this->assertSame($post->getMorphClass(), $timeline->auditableType);
        $this->assertSame('7', $timeline->auditableId);
    }

    public function test_it_reads_the_timeline_for_a_type_and_id(): void
    {
        $this->repository->save($this->record('App\\Models\\Order', '9'));

        $timeline = $this->reader->for('App\\Models\\Order', 9);

        $this->assertSame(1, $timeline->count());
        $this->assertSame('9', $timeline->auditableId);
    }

    public function test_an_unknown_auditable_yields_an_empty_timeline(): void
    {
        $this->assertTrue($this->reader->for('App\\Models\\Order', 1)->isEmpty());
    }

    public function test_it_reconstructs_the_state_for_a_model_instance(): void
    {
        $post = new Post;
        $post->setRawAttributes(['id' => 7], true);

        $this->repository->save($this->record($post->getMorphClass(), '7'));

        $state = $this->reader->stateAt($post, null, new DateTimeImmutable('2026-02-01T00:00:00+00:00'));

        $this->assertTrue($state->existed);
        $this->assertSame('7', $state->auditableId);
    }

    private function record(string $type, string $id): AuditRecord
    {
        return new AuditRecord(
            auditable: new AuditableReference($type, $id),
            event: ChangeType::Created,
            diff: Diff::empty(),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
        );
    }
}
