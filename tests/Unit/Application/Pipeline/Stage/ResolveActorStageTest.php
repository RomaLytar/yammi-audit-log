<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Application\Pipeline\Stage;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Application\DTO\ChangeData;
use Yammi\AuditLog\Application\Pipeline\RecordChangeContext;
use Yammi\AuditLog\Application\Pipeline\Stage\ResolveActorStage;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Tests\Support\FixedActorResolver;

final class ResolveActorStageTest extends TestCase
{
    public function test_it_puts_the_resolved_actor_and_origin_on_the_context(): void
    {
        $actor = Actor::job('App\\Jobs\\Sync');
        $origin = Actor::user('1', 'Jane');
        $stage = new ResolveActorStage(new FixedActorResolver($actor, $origin));

        $context = $stage(RecordChangeContext::start($this->change()));

        $this->assertSame($actor, $context->actor);
        $this->assertSame($origin, $context->origin);
    }

    public function test_a_missing_origin_stays_null(): void
    {
        $stage = new ResolveActorStage(new FixedActorResolver(Actor::system()));

        $context = $stage(RecordChangeContext::start($this->change()));

        $this->assertSame(Actor::system()->type, $context->actor?->type);
        $this->assertNull($context->origin);
    }

    private function change(): ChangeData
    {
        return new ChangeData(
            auditableType: 'App\\Models\\Order',
            auditableId: '1',
            event: ChangeType::Updated,
            before: [],
            after: [],
        );
    }
}
