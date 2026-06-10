<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\Enum;

enum ActorType: string
{
    case User = 'user';
    case Job = 'job';
    case Command = 'command';
    case Scheduler = 'scheduler';
    case System = 'system';
    case Unknown = 'unknown';

    public function isIdentified(): bool
    {
        return $this !== self::System && $this !== self::Unknown;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
