<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Integration;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Optional link to the sibling JobsMonitor dashboard: when its URL is
 * configured, job actors in the audit UI link straight to the monitor,
 * answering "why did this change" with the job that caused it.
 *
 * @internal
 */
final class JobsMonitorBridge
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function url(): ?string
    {
        $url = $this->config->get('audit-log.integrations.jobs_monitor.url');

        return is_string($url) && $url !== '' ? $url : null;
    }
}
