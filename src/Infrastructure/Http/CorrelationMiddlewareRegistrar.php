<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Yammi\AuditLog\Infrastructure\Http\Middleware\StartAuditCorrelation;

/**
 * Pushes the correlation middleware onto the HTTP kernel so every request starts
 * a fresh correlation for the changes it makes.
 */
final class CorrelationMiddlewareRegistrar
{
    public function __construct(
        private readonly Application $app,
    ) {}

    public function register(): void
    {
        $this->app->booted(function (): void {
            $kernel = $this->app->make(HttpKernelContract::class);

            if ($kernel instanceof HttpKernel) {
                $kernel->pushMiddleware(StartAuditCorrelation::class);
            }
        });
    }
}
