<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\Exception;

use DomainException;

final class InvalidAuditData extends DomainException
{
    public static function emptyValue(string $what): self
    {
        return new self("Audit data is invalid: {$what} must not be empty.");
    }

    public static function unknownEvent(string $event): self
    {
        return new self("Audit data is invalid: \"{$event}\" is not a known change type.");
    }

    public static function invalidDate(string $value): self
    {
        return new self("Audit data is invalid: \"{$value}\" is not a valid date.");
    }

    public static function notManyToMany(string $relation): self
    {
        return new self("Audit data is invalid: \"{$relation}\" is not a many-to-many relation.");
    }
}
