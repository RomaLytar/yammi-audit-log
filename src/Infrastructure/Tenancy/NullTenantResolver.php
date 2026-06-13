<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Tenancy;

use Yammi\AuditLog\Application\Contract\Resolver\TenantResolver;

/** @internal */
final class NullTenantResolver implements TenantResolver
{
    public function resolve(): ?string
    {
        return null;
    }
}
