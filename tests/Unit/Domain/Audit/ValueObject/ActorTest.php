<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Domain\Audit\ValueObject;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

final class ActorTest extends TestCase
{
    public function test_it_builds_a_user_actor(): void
    {
        $actor = Actor::user('42', 'John Doe');

        $this->assertSame(ActorType::User, $actor->type);
        $this->assertSame('42', $actor->identifier);
        $this->assertSame('John Doe', $actor->label);
        $this->assertFalse($actor->isAnonymous());
    }

    public function test_a_job_actor_falls_back_to_the_class_as_label(): void
    {
        $actor = Actor::job('App\\Jobs\\ProcessPayment');

        $this->assertSame(ActorType::Job, $actor->type);
        $this->assertSame('App\\Jobs\\ProcessPayment', $actor->displayLabel());
    }

    public function test_system_and_unknown_actors_are_anonymous(): void
    {
        $this->assertTrue(Actor::system()->isAnonymous());
        $this->assertTrue(Actor::unknown()->isAnonymous());
    }

    public function test_display_label_falls_back_through_label_identifier_then_type(): void
    {
        $this->assertSame('John', (Actor::user('1', 'John'))->displayLabel());
        $this->assertSame('1', (Actor::user('1'))->displayLabel());
        $this->assertSame('System', Actor::system()->displayLabel());
    }

    public function test_equality_is_by_value(): void
    {
        $this->assertTrue(Actor::user('1', 'A')->equals(Actor::user('1', 'A')));
        $this->assertFalse(Actor::user('1', 'A')->equals(Actor::user('1', 'B')));
        $this->assertFalse(Actor::user('1')->equals(Actor::command('1')));
    }
}
