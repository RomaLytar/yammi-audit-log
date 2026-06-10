<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\ValueObject;

use Yammi\AuditLog\Domain\Audit\Enum\ActorType;

final class Actor
{
    private const MAX_LENGTH = 191;

    public readonly ActorType $type;

    public readonly ?string $identifier;

    public readonly ?string $label;

    public function __construct(ActorType $type, ?string $identifier = null, ?string $label = null)
    {
        $this->type = $type;
        $this->identifier = self::normalize($identifier);
        $this->label = self::normalize($label);
    }

    private static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, self::MAX_LENGTH);
    }

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
