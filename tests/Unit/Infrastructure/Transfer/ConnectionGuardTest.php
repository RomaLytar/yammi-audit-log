<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Transfer;

use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Transfer\ConnectionGuard;

final class ConnectionGuardTest extends TestCase
{
    public function test_it_allows_only_declared_connections(): void
    {
        $guard = new ConnectionGuard;

        $this->assertTrue($guard->allows('audit', ['mysql', 'audit']));
        $this->assertFalse($guard->allows('attacker', ['mysql', 'audit']));
        $this->assertFalse($guard->allows('', ['mysql', 'audit']));
        $this->assertFalse($guard->allows('audit', []));
    }

    public function test_it_accepts_bare_identifiers(): void
    {
        $guard = new ConnectionGuard;

        $this->assertTrue($guard->isSafeDatabaseName('audit_db'));
        $this->assertTrue($guard->isSafeDatabaseName('AuditDB1'));
    }

    public function test_it_rejects_identifiers_with_unsafe_characters(): void
    {
        $guard = new ConnectionGuard;

        $this->assertFalse($guard->isSafeDatabaseName('audit`db'));
        $this->assertFalse($guard->isSafeDatabaseName('audit db'));
        $this->assertFalse($guard->isSafeDatabaseName('audit;DROP'));
        $this->assertFalse($guard->isSafeDatabaseName('audit-db'));
        $this->assertFalse($guard->isSafeDatabaseName(''));
    }
}
