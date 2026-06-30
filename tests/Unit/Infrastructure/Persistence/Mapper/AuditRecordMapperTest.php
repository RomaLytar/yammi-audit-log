<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Persistence\Mapper;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Infrastructure\Persistence\Mapper\AuditRecordMapper;
use Yammi\AuditLog\Tests\Support\FixedTenantResolver;

final class AuditRecordMapperTest extends TestCase
{
    private AuditRecordMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new AuditRecordMapper;
    }

    public function test_to_row_flattens_the_domain_record(): void
    {
        $row = $this->mapper->toRow(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 7),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::user('1', 'Jane'),
            origin: Actor::command('app:sync'),
            labels: new LabelSnapshot(['user_id' => 'Jane']),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            correlationId: 'corr-1',
            isNoise: true,
        ));

        $this->assertSame('App\\Models\\Order', $row->auditableType);
        $this->assertSame('7', $row->auditableId);
        $this->assertSame('updated', $row->event);
        $this->assertSame(['status' => ['old' => 'a', 'new' => 'b']], $row->changes);
        $this->assertSame('user', $row->actorType);
        $this->assertSame('1', $row->actorId);
        $this->assertSame('Jane', $row->actorLabel);
        $this->assertSame('command', $row->originType);
        $this->assertSame('app:sync', $row->originId);
        $this->assertSame('app:sync', $row->originLabel);
        $this->assertSame(['user_id' => 'Jane'], $row->labels);
        $this->assertSame('corr-1', $row->correlationId);
        $this->assertTrue($row->isNoise);
        $this->assertSame('2026-01-01 10:00:00', $row->occurredAt);
        $this->assertNull($row->tenantId);
    }

    public function test_to_row_stamps_the_current_tenant(): void
    {
        FixedTenantResolver::$tenant = 'acme';

        try {
            $row = (new AuditRecordMapper(new FixedTenantResolver))->toRow(new AuditRecord(
                auditable: AuditableReference::to('App\\Models\\Order', 7),
                event: ChangeType::Updated,
                diff: Diff::between(['status' => 'a'], ['status' => 'b']),
                actor: Actor::user('1', 'Jane'),
                origin: null,
                labels: LabelSnapshot::empty(),
                occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            ));
        } finally {
            FixedTenantResolver::$tenant = null;
        }

        $this->assertSame('acme', $row->tenantId);
    }

    public function test_to_domain_rebuilds_the_record_from_a_model(): void
    {
        $record = $this->mapper->toDomain($this->model([
            'changes' => (string) json_encode(['status' => ['old' => 'a', 'new' => 'b']]),
            'labels' => (string) json_encode(['user_id' => 'Jane']),
        ]));

        $this->assertSame(12, $record->id());
        $this->assertSame('App\\Models\\Order', $record->auditable()->type);
        $this->assertSame('7', $record->auditable()->id);
        $this->assertSame(ChangeType::Updated, $record->event());
        $this->assertSame('a', $record->diff()->field('status')?->old);
        $this->assertSame('b', $record->diff()->field('status')?->new);
        $this->assertSame(ActorType::User, $record->actor()->type);
        $this->assertSame('Jane', $record->actor()->label);
        $this->assertSame(ActorType::Command, $record->origin()?->type);
        $this->assertSame('Jane', $record->labels()->for('user_id'));
        $this->assertSame('corr-1', $record->correlationId());
        $this->assertTrue($record->isNoise());
        $this->assertSame('2026-01-01 10:00:00', $record->occurredAt()->format('Y-m-d H:i:s'));
    }

    public function test_to_row_and_to_domain_carry_the_reason(): void
    {
        $row = $this->mapper->toRow(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 7),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            reason: 'ticket #4521',
        ));

        $this->assertSame('ticket #4521', $row->reason);

        $record = $this->mapper->toDomain($this->model(['reason' => 'ticket #4521']));
        $this->assertSame('ticket #4521', $record->reason());
    }

    public function test_to_row_and_to_domain_carry_the_span(): void
    {
        $row = $this->mapper->toRow(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 7),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            spanId: 'span-9',
            parentSpanId: 'span-8',
        ));

        $this->assertSame('span-9', $row->spanId);
        $this->assertSame('span-8', $row->parentSpanId);
        $this->assertSame('span-9', $row->toArray()['span_id']);
        $this->assertSame('span-8', $row->toArray()['parent_span_id']);

        $record = $this->mapper->toDomain($this->model([
            'span_id' => 'span-9',
            'parent_span_id' => 'span-8',
        ]));

        $this->assertSame('span-9', $record->spanId());
        $this->assertSame('span-8', $record->parentSpanId());
    }

    public function test_to_row_and_to_domain_carry_the_trace(): void
    {
        $row = $this->mapper->toRow(new AuditRecord(
            auditable: AuditableReference::to('App\\Models\\Order', 7),
            event: ChangeType::Updated,
            diff: Diff::between(['status' => 'a'], ['status' => 'b']),
            actor: Actor::system(),
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            traceId: '4bf92f3577b34da6a3ce929d0e0e4736',
        ));

        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $row->traceId);
        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $row->toArray()['trace_id']);

        $record = $this->mapper->toDomain($this->model(['trace_id' => '4bf92f3577b34da6a3ce929d0e0e4736']));
        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $record->traceId());
    }

    public function test_to_domain_without_a_span(): void
    {
        $record = $this->mapper->toDomain($this->model());

        $this->assertNull($record->spanId());
        $this->assertNull($record->parentSpanId());
    }

    public function test_to_domain_without_an_origin(): void
    {
        $record = $this->mapper->toDomain($this->model([
            'origin_type' => null,
            'origin_id' => null,
            'origin_label' => null,
        ]));

        $this->assertNull($record->origin());
    }

    public function test_to_domain_tolerates_malformed_changes_and_labels(): void
    {
        $record = $this->mapper->toDomain($this->model([
            'changes' => (string) json_encode(['status' => 'not-a-pair']),
            'labels' => (string) json_encode(['user_id' => ['nested']]),
        ]));

        $this->assertNull($record->diff()->field('status')?->old);
        $this->assertNull($record->diff()->field('status')?->new);
        $this->assertSame('', $record->labels()->for('user_id'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function model(array $overrides = []): AuditRecordModel
    {
        $model = new AuditRecordModel;
        $model->setRawAttributes(array_merge([
            'id' => 12,
            'auditable_type' => 'App\\Models\\Order',
            'auditable_id' => '7',
            'event' => 'updated',
            'changes' => (string) json_encode([]),
            'actor_type' => 'user',
            'actor_id' => '1',
            'actor_label' => 'Jane',
            'origin_type' => 'command',
            'origin_id' => 'app:sync',
            'origin_label' => 'app:sync',
            'labels' => (string) json_encode([]),
            'correlation_id' => 'corr-1',
            'is_noise' => 1,
            'occurred_at' => '2026-01-01 10:00:00',
        ], $overrides), true);

        return $model;
    }
}
