<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Actor;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Actor\ActorContext;

final class ActorContextTest extends TestCase
{
    public function test_jobs_are_tracked_as_a_stack(): void
    {
        $context = new ActorContext;

        $this->assertNull($context->currentJob());

        $context->enterJob('App\\Jobs\\Outer');
        $context->enterJob('App\\Jobs\\Inner');
        $this->assertSame('App\\Jobs\\Inner', $context->currentJob());

        $context->leaveJob();
        $this->assertSame('App\\Jobs\\Outer', $context->currentJob());

        $context->leaveJob();
        $this->assertNull($context->currentJob());
    }

    public function test_it_tracks_the_current_command(): void
    {
        $context = new ActorContext;

        $this->assertNull($context->currentCommand());

        $context->enterCommand('app:sync');
        $this->assertSame('app:sync', $context->currentCommand());
    }
}
