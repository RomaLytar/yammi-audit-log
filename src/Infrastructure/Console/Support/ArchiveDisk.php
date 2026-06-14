<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Resolves the filesystem disk shared by the archive and subject-report
 * commands: a non-empty --disk override wins, otherwise the configured archive
 * disk, otherwise "local".
 *
 * @internal
 */
final class ArchiveDisk
{
    public function name(mixed $option, ConfigRepository $config): string
    {
        if (is_string($option) && $option !== '') {
            return $option;
        }

        $configured = $config->get('audit-log.archive.disk', 'local');

        return is_string($configured) && $configured !== '' ? $configured : 'local';
    }
}
