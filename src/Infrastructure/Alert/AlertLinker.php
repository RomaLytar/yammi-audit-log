<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Alert;

use Illuminate\Contracts\Routing\UrlGenerator;
use Throwable;

/**
 * Builds dashboard deep links for alert messages; returns null when the
 * bundled UI (and its routes) are disabled so alerts still go out.
 *
 * @internal
 */
final class AlertLinker
{
    public function __construct(
        private readonly UrlGenerator $urls,
    ) {}

    /**
     * @param  array<string, string>  $params
     */
    public function to(string $route, array $params = []): ?string
    {
        try {
            return $this->urls->route($route, $params);
        } catch (Throwable) {
            return null;
        }
    }
}
