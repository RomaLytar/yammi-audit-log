<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use Illuminate\Console\Command;
use Throwable;
use Yammi\AuditLog\Domain\Settings\Repository\GeneralSettingRepository;
use Yammi\AuditLog\Infrastructure\Integrity\IntegrityHasher;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

/** @internal */
final class VerifyIntegrityCommand extends Command
{
    private const CHUNK = 500;

    protected $signature = 'audit-log:verify';

    protected $description = 'Verify the audit hash chain and name the first tampered record';

    public function handle(IntegrityHasher $hasher, GeneralSettingRepository $settings): int
    {
        $previous = $this->anchor($settings);
        $verified = 0;
        $unhashed = 0;
        $brokenId = null;

        AuditRecordModel::query()
            ->withoutGlobalScopes()
            ->orderBy('id')
            ->chunk(self::CHUNK, function ($models) use ($hasher, &$previous, &$verified, &$unhashed, &$brokenId): bool {
                foreach ($models as $model) {
                    $stored = $model->getAttribute('integrity_hash');

                    if (! is_string($stored) || $stored === '') {
                        $unhashed++;
                        $previous = null;

                        continue;
                    }

                    if ($hasher->hash($previous, $model->getAttributes()) !== $stored) {
                        $brokenId = (int) $model->getKey();

                        return false;
                    }

                    $previous = $stored;
                    $verified++;
                }

                return true;
            });

        if ($brokenId !== null) {
            $this->error("Integrity FAILED at record #{$brokenId}: the stored hash does not match the chain.");

            return self::FAILURE;
        }

        $this->info("Integrity OK: {$verified} hashed record(s) verified, {$unhashed} recorded without hashing.");

        return self::SUCCESS;
    }

    private function anchor(GeneralSettingRepository $settings): ?string
    {
        try {
            $anchor = $settings->get('integrity', 'chain_anchor');
        } catch (Throwable) {
            return null;
        }

        return is_string($anchor) && $anchor !== '' ? $anchor : null;
    }
}
