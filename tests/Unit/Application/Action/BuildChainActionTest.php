<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Action;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\Action\Read\BuildChainAction;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;
use Yammi\AuditLog\Tests\Support\InMemoryAuditRecordRepository;

final class BuildChainActionTest extends TestCase
{
    public function test_it_builds_a_chain_across_models_from_one_correlation(): void
    {
        $repository = new InMemoryAuditRecordRepository;
        $repository->save($this->record('App\\Models\\Order', Actor::user('5', 'Jane Doe'), 'trace-1'));
        $repository->save($this->record('App\\Models\\Invoice', Actor::job('App\\Jobs\\ProcessOrder'), 'trace-1'));
        $repository->save($this->record('App\\Models\\Payment', Actor::job('App\\Jobs\\ProcessOrder'), 'trace-1'));
        $repository->save($this->record('App\\Models\\Order', Actor::system(), 'other-trace'));

        $chain = (new BuildChainAction($repository))('trace-1');

        $this->assertNotNull($chain);
        $this->assertSame(3, $chain->count());
        $this->assertSame(3, $chain->modelCount);
        $this->assertSame('Jane Doe', $chain->rootActorLabel);
        $this->assertSame('Order', $chain->rootModel);
    }

    public function test_an_unknown_correlation_returns_null(): void
    {
        $this->assertNull((new BuildChainAction(new InMemoryAuditRecordRepository))('nope'));
    }

    private function record(string $type, Actor $actor, string $correlationId): AuditRecord
    {
        return new AuditRecord(
            auditable: AuditableReference::to($type, 1),
            event: ChangeType::Created,
            diff: Diff::empty(),
            actor: $actor,
            origin: null,
            labels: LabelSnapshot::empty(),
            occurredAt: new DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            correlationId: $correlationId,
        );
    }
}
