<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Transfer;

/**
 * Server-side guard for the database transfer: only connections declared in
 * config/database.php may be used, and a database name interpolated into a
 * CREATE DATABASE statement must be a bare identifier.
 *
 * @internal
 */
final class ConnectionGuard
{
    /**
     * @param  array<array-key, string>  $allowed
     */
    public function allows(string $name, array $allowed): bool
    {
        return $name !== '' && in_array($name, array_values($allowed), true);
    }

    public function isSafeDatabaseName(string $name): bool
    {
        return preg_match('/^[A-Za-z0-9_]+$/', $name) === 1;
    }
}
