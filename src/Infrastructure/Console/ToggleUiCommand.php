<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use Illuminate\Console\Command;
use Throwable;
use Yammi\AuditLog\Application\Service\SettingRegistry;
use Yammi\AuditLog\Domain\Settings\Repository\GeneralSettingRepository;

/** @internal */
final class ToggleUiCommand extends Command
{
    protected $signature = 'audit-log:ui {state : enable or disable}';

    protected $description = 'Enable or disable the bundled audit dashboard (off by default)';

    public function handle(GeneralSettingRepository $settings): int
    {
        $state = $this->argument('state');

        if (! in_array($state, ['enable', 'disable'], true)) {
            $this->error('Use: audit-log:ui enable | audit-log:ui disable');

            return self::FAILURE;
        }

        $enable = $state === 'enable';

        try {
            $settings->set(SettingRegistry::GROUP_UI, 'ui_enabled', $enable ? '1' : '0', 'boolean');
        } catch (Throwable) {
            $this->error('Could not store the toggle — run the package migrations first (php artisan migrate).');

            return self::FAILURE;
        }

        $this->info($enable
            ? 'Dashboard enabled. It is served under the configured path from the next request.'
            : 'Dashboard disabled. The audit capture keeps running; only the UI routes are gone.');

        return self::SUCCESS;
    }
}
