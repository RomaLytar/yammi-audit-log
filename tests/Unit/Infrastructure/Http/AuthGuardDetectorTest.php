<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Http;

use Illuminate\Auth\Middleware\Authenticate;
use PHPUnit\Framework\TestCase;
use Yammi\AuditLog\Infrastructure\Http\AuthGuardDetector;

final class AuthGuardDetectorTest extends TestCase
{
    public function test_it_recognises_the_auth_alias(): void
    {
        $this->assertTrue((new AuthGuardDetector)->hasGuard(['api', 'auth']));
    }

    public function test_it_recognises_a_parameterised_guard(): void
    {
        $this->assertTrue((new AuthGuardDetector)->hasGuard(['web', 'auth:sanctum']));
    }

    public function test_it_recognises_an_authorization_gate(): void
    {
        $this->assertTrue((new AuthGuardDetector)->hasGuard(['api', 'can:view-audit']));
    }

    public function test_it_recognises_the_authenticate_middleware_class(): void
    {
        $this->assertTrue((new AuthGuardDetector)->hasGuard([Authenticate::class]));
        $this->assertTrue((new AuthGuardDetector)->hasGuard(['\\'.Authenticate::class]));
    }

    public function test_it_rejects_a_stack_without_a_guard(): void
    {
        $this->assertFalse((new AuthGuardDetector)->hasGuard(['api']));
        $this->assertFalse((new AuthGuardDetector)->hasGuard(['web', 'throttle:60,1']));
        $this->assertFalse((new AuthGuardDetector)->hasGuard([]));
    }

    public function test_it_ignores_non_string_entries(): void
    {
        $this->assertFalse((new AuthGuardDetector)->hasGuard([['auth'], 123, null]));
    }
}
