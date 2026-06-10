<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\ValueObject;

use Yammi\AuditLog\Domain\Audit\Enum\ActorType;

final class Actor
{
    public function __construct(
        public readonly ActorType $type,
        public readonly ?string $identifier = null,
        public readonly ?string $label = null,
    ) {}

    public static function user(string $identifier, ?string $label = null): self
    {
        return new self(ActorType::User, $identifier, $label);
    }

    public static function job(string $jobClass, ?string $label = null): self
    {
        return new self(ActorType::Job, $jobClass, $label ?? $jobClass);
    }

    public static function command(string $name, ?string $label = null): self
    {
        return new self(ActorType::Command, $name, $label ?? $name);
    }

    public static function scheduler(string $name, ?string $label = null): self
    {
        return new self(ActorType::Scheduler, $name, $label ?? $name);
    }

    public static function system(): self
    {
        return new self(ActorType::System);
    }

    public static function unknown(): self
    {
        return new self(ActorType::Unknown);
    }

    public function isAnonymous(): bool
    {
        return ! $this->type->isIdentified();
    }

    public function displayLabel(): string
    {
        return $this->label ?? $this->identifier ?? $this->type->label();
    }

    public function equals(self $other): bool
    {
        return $this->type === $other->type
            && $this->identifier === $other->identifier
            && $this->label === $other->label;
    }
}
