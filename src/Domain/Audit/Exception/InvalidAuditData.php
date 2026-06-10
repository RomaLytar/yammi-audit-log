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
}
