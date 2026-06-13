<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;

/**
 * Decides whether a route middleware stack enforces authentication or
 * authorization, so the provider can fail closed before registering routes
 * that would otherwise expose audit data without a guard.
 *
 * @internal
 */
final class AuthGuardDetector
{
    private const ALIAS_GUARDS = ['auth', 'can'];

    private const CLASS_GUARDS = [
        Authenticate::class,
        AuthenticateWithBasicAuth::class,
        Authorize::class,
    ];

    /**
     * @param  array<array-key, mixed>  $middleware
     */
    public function hasGuard(array $middleware): bool
    {
        foreach ($middleware as $entry) {
            if (is_string($entry) && $this->isGuard($entry)) {
                return true;
            }
        }

        return false;
    }

    private function isGuard(string $entry): bool
    {
        $name = trim($entry);
        $base = str_contains($name, ':') ? substr($name, 0, (int) strpos($name, ':')) : $name;

        return in_array($base, self::ALIAS_GUARDS, true)
            || in_array(ltrim($base, '\\'), self::CLASS_GUARDS, true);
    }
}
