<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Provider;

use Yammi\AuditLog\Application\Contract\Resolver\TenantResolver;
use Yammi\AuditLog\Infrastructure\Tenancy\NullTenantResolver;

/**
 * Multi-tenancy: the host-provided tenant resolver, or a null object.
 *
 * @internal
 */
final class TenancyBindings extends BindingRegistrar
{
    public function register(): void
    {
        $this->app->singleton(TenantResolver::class, function (): TenantResolver {
            $resolver = $this->config()->get('audit-log.tenancy.resolver');

            if (is_string($resolver) && is_subclass_of($resolver, TenantResolver::class)) {
                $instance = $this->app->make($resolver);

                if ($instance instanceof TenantResolver) {
                    return $instance;
                }
            }

            return new NullTenantResolver;
        });
    }
}
