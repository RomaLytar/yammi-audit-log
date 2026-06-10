<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Domain\Audit\Enum;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;

final class ActorTypeTest extends TestCase
{
    public function test_user_job_command_and_scheduler_are_identified(): void
    {
        $this->assertTrue(ActorType::User->isIdentified());
        $this->assertTrue(ActorType::Job->isIdentified());
        $this->assertTrue(ActorType::Command->isIdentified());
        $this->assertTrue(ActorType::Scheduler->isIdentified());
    }

    public function test_system_and_unknown_are_not_identified(): void
    {
        $this->assertFalse(ActorType::System->isIdentified());
        $this->assertFalse(ActorType::Unknown->isIdentified());
    }
}
