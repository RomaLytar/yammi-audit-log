<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Support;

use DateTimeZone;
use Exception;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Resolves the timezone timestamps are DISPLAYED in. Records are stored in
 * the application timezone; operators in another zone set
 * audit-log.timezone (or the dashboard setting) to read local wall-clock
 * times. Empty means the application timezone; an invalid name falls back
 * to UTC so a typo never throws.
 *
 * @internal
 */
final class AuditTimezone
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function name(): string
    {
        $configured = $this->config->get('audit-log.timezone');

        if (is_string($configured) && $configured !== '' && $this->isValid($configured)) {
            return $configured;
        }

        $app = $this->config->get('app.timezone');

        if (is_string($app) && $app !== '' && $this->isValid($app)) {
            return $app;
        }

        return 'UTC';
    }

    private function isValid(string $timezone): bool
    {
        try {
            new DateTimeZone($timezone);

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
