<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\Read\ReconstructStateAction;
use Yammi\AuditLog\Application\DTO\Audit\StateData;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;

final class ReconstructStateActionTest extends TestCase
{
    private InMemoryAuditRecordRepository $repository;

    private AuditableReference $reference;

    protected function setUp(): void
    {
        $this->repository = new InMemoryAuditRecordRepository;
        $this->reference = AuditableReference::to('App\\Models\\Order', '42');
    }

    public function test_it_folds_diffs_into_the_state_at_a_moment(): void
    {
        $this->saveRecord(ChangeType::Created, [], ['status' => 'new', 'total' => 100], '2026-03-01T10:00:00+00:00');
        $this->saveRecord(ChangeType::Updated, ['status' => 'new'], ['status' => 'paid'], '2026-03-02T10:00:00+00:00');
        $this->saveRecord(ChangeType::Updated, ['total' => 100], ['total' => 250], '2026-03-03T10:00:00+00:00');

        $state = $this->reconstructAt('2026-03-02T12:00:00+00:00');

        $this->assertTrue($state->existed);
        $this->assertSame(['status' => 'paid', 'total' => 100], $state->attributes);
        $this->assertSame(2, $state->appliedChanges);
        $this->assertSame('2026-03-02T10:00:00+00:00', $state->lastChangeAt);
    }

    public function test_before_creation_the_record_did_not_exist(): void
    {
        $this->saveRecord(ChangeType::Created, [], ['status' => 'new'], '2026-03-01T10:00:00+00:00');

        $state = $this->reconstructAt('2026-02-01T00:00:00+00:00');

        $this->assertFalse($state->existed);
        $this->assertSame([], $state->attributes);
        $this->assertSame(0, $state->appliedChanges);
        $this->assertNull($state->lastChangeAt);
    }

    public function test_a_deletion_keeps_the_last_known_values_but_marks_the_record_gone(): void
    {
        $this->saveRecord(ChangeType::Created, [], ['status' => 'new'], '2026-03-01T10:00:00+00:00');
        $this->saveRecord(ChangeType::Deleted, ['status' => 'new'], [], '2026-03-02T10:00:00+00:00');

        $state = $this->reconstructAt('2026-03-05T00:00:00+00:00');

        $this->assertFalse($state->existed);
        $this->assertSame(['status' => 'new'], $state->attributes);
    }

    public function test_a_restore_brings_the_record_back(): void
    {
        $this->saveRecord(ChangeType::Created, [], ['status' => 'new'], '2026-03-01T10:00:00+00:00');
        $this->saveRecord(ChangeType::Deleted, ['status' => 'new'], [], '2026-03-02T10:00:00+00:00');
        $this->saveRecord(ChangeType::Restored, [], [], '2026-03-03T10:00:00+00:00');

        $state = $this->reconstructAt('2026-03-05T00:00:00+00:00');

        $this->assertTrue($state->existed);
        $this->assertSame(['status' => 'new'], $state->attributes);
    }

    public function test_a_history_starting_mid_life_still_counts_as_existing(): void
    {
        $this->saveRecord(ChangeType::Updated, ['status' => 'new'], ['status' => 'paid'], '2026-03-02T10:00:00+00:00');

        $state = $this->reconstructAt('2026-03-05T00:00:00+00:00');

        $this->assertTrue($state->existed);
        $this->assertSame(['status' => 'paid'], $state->attributes);
    }

    public function test_other_records_do_not_leak_into_the_state(): void
    {
        $this->saveRecord(ChangeType::Created, [], ['status' => 'new'], '2026-03-01T10:00:00+00:00');

        $this->repository->save(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', '99'),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'new'], ['status' => 'cancelled']),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-03-02T10:00:00+00:00'),
        ));

        $state = $this->reconstructAt('2026-03-05T00:00:00+00:00');

        $this->assertSame(['status' => 'new'], $state->attributes);
        $this->assertSame(1, $state->appliedChanges);
    }

    private function saveRecord(ChangeType $event, array $before, array $after, string $occurredAt): void
    {
        $this->repository->save(new AuditRecord(
            auditable: $this->reference,
            event: $event,
            diff: Diff::between($before, $after),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable($occurredAt),
        ));
    }

    private function reconstructAt(string $moment): StateData
    {
        return (new ReconstructStateAction($this->repository))(
            $this->reference,
            new DateTimeImmutable($moment),
        );
    }
}
