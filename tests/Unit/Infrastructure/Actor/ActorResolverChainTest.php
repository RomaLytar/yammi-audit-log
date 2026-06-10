<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Actor;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Infrastructure\Actor\ActorContext;
use Yammi\AuditLog\Infrastructure\Actor\ActorResolverChain;
use Yammi\AuditLog\Tests\Support\FixedActorProvider;

final class ActorResolverChainTest extends TestCase
{
    public function test_it_returns_the_first_provider_that_resolves_an_actor(): void
    {
        $chain = new ActorResolverChain([
            new FixedActorProvider(null),
            new FixedActorProvider(Actor::job('App\\Jobs\\ProcessPayment')),
            new FixedActorProvider(Actor::user('1', 'Jane')),
        ], new ActorContext);

        $this->assertSame('App\\Jobs\\ProcessPayment', $chain->resolve()->displayLabel());
    }

    public function test_it_falls_back_to_a_system_actor_when_nothing_resolves(): void
    {
        $chain = new ActorResolverChain([new FixedActorProvider(null)], new ActorContext);

        $this->assertSame(ActorType::System, $chain->resolve()->type);
    }

    public function test_origin_comes_from_the_current_job_frame(): void
    {
        $context = new ActorContext;
        $chain = new ActorResolverChain([], $context);

        $this->assertNull($chain->resolveOrigin());

        $context->enterJob('App\\Jobs\\ProcessPayment', Actor::user('5', 'John Doe'));

        $this->assertSame('John Doe', $chain->resolveOrigin()?->displayLabel());
    }

    public function test_an_anonymous_origin_is_treated_as_no_origin(): void
    {
        $context = new ActorContext;
        $context->enterJob('App\\Jobs\\ProcessPayment', Actor::system());

        $chain = new ActorResolverChain([], $context);

        $this->assertNull($chain->resolveOrigin());
    }
}
