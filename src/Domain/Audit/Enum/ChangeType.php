<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\Enum;

enum ChangeType: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Attached = 'attached';
    case Detached = 'detached';
    case Synced = 'synced';
    case Accessed = 'accessed';

    public function isCreation(): bool
    {
        return $this === self::Created;
    }

    public function isDeletion(): bool
    {
        return $this === self::Deleted;
    }

    public function isPivot(): bool
    {
        return $this === self::Attached
            || $this === self::Detached
            || $this === self::Synced;
    }

    public function isAccess(): bool
    {
        return $this === self::Accessed;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
