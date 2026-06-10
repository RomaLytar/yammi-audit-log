<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Actor\Provider;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Infrastructure\Actor\ActorContext;
use Yammi\AuditLog\Infrastructure\Actor\Provider\ConsoleActorProvider;
use Yammi\AuditLog\Infrastructure\Actor\Provider\QueuedJobActorProvider;

final class ActorProvidersTest extends TestCase
{
    public function test_queued_job_provider_resolves_the_current_job(): void
    {
        $context = new ActorContext;
        $provider = new QueuedJobActorProvider($context);

        $this->assertNull($provider->resolve());

        $context->enterJob('App\\Jobs\\ProcessPayment');

        $actor = $provider->resolve();
        $this->assertSame(ActorType::Job, $actor?->type);
        $this->assertSame('App\\Jobs\\ProcessPayment', $actor?->displayLabel());
    }

    public function test_console_provider_resolves_the_current_command(): void
    {
        $context = new ActorContext;
        $provider = new ConsoleActorProvider($context);

        $this->assertNull($provider->resolve());

        $context->enterCommand('app:sync');

        $actor = $provider->resolve();
        $this->assertSame(ActorType::Command, $actor?->type);
        $this->assertSame('app:sync', $actor?->displayLabel());
    }
}
