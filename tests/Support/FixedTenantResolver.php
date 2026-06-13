<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use Yammi\AuditLog\Application\Contract\Resolver\TenantResolver;

final class FixedTenantResolver implements TenantResolver
{
    public static ?string $tenant = null;

    public function resolve(): ?string
    {
        return self::$tenant;
    }
}
