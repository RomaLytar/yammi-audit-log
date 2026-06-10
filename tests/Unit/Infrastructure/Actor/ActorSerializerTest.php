<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Actor;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Infrastructure\Actor\ActorSerializer;

final class ActorSerializerTest extends TestCase
{
    public function test_it_round_trips_an_actor(): void
    {
        $serializer = new ActorSerializer;

        $array = $serializer->toArray(Actor::user('5', 'John Doe'));
        $actor = $serializer->fromArray($array);

        $this->assertSame(ActorType::User, $actor?->type);
        $this->assertSame('5', $actor?->identifier);
        $this->assertSame('John Doe', $actor?->label);
    }

    public function test_it_returns_null_for_an_unknown_type(): void
    {
        $serializer = new ActorSerializer;

        $this->assertNull($serializer->fromArray(['type' => 'martian']));
        $this->assertNull($serializer->fromArray([]));
    }
}
