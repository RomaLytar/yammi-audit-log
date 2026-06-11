<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Actor;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
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

    public function test_each_job_frame_carries_its_origin(): void
    {
        $context = new ActorContext;

        $this->assertNull($context->currentOrigin());

        $context->enterJob('App\\Jobs\\Outer', Actor::user('1', 'Jane'));
        $this->assertSame('Jane', $context->currentOrigin()?->displayLabel());

        $context->enterJob('App\\Jobs\\Inner', Actor::user('2', 'John'));
        $this->assertSame('John', $context->currentOrigin()?->displayLabel());

        $context->leaveJob();
        $this->assertSame('Jane', $context->currentOrigin()?->displayLabel());
    }

    public function test_it_tracks_the_current_command(): void
    {
        $context = new ActorContext;

        $this->assertNull($context->currentCommand());

        $context->enterCommand('app:sync');
        $this->assertSame('app:sync', $context->currentCommand());
    }

    public function test_commands_are_tracked_as_a_stack(): void
    {
        $context = new ActorContext;

        $context->enterCommand('app:outer');
        $context->enterCommand('app:inner');
        $this->assertSame('app:inner', $context->currentCommand());

        $context->leaveCommand();
        $this->assertSame('app:outer', $context->currentCommand());

        $context->leaveCommand();
        $this->assertNull($context->currentCommand());
    }

    public function test_leaving_a_command_on_an_empty_stack_is_harmless(): void
    {
        $context = new ActorContext;

        $context->leaveCommand();

        $this->assertNull($context->currentCommand());
    }

    public function test_scheduled_tasks_are_tracked_as_a_stack(): void
    {
        $context = new ActorContext;

        $this->assertNull($context->currentScheduledTask());

        $context->enterScheduledTask('audit-log:prune');
        $this->assertSame('audit-log:prune', $context->currentScheduledTask());

        $context->leaveScheduledTask();
        $this->assertNull($context->currentScheduledTask());

        $context->leaveScheduledTask();
        $this->assertNull($context->currentScheduledTask());
    }
}
