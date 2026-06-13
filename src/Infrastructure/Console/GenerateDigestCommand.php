<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use Illuminate\Console\Command;
use Yammi\AuditLog\Infrastructure\Integrity\DigestRecorder;
use Yammi\AuditLog\Infrastructure\Integrity\IntegritySigner;

/** @internal */
final class GenerateDigestCommand extends Command
{
    protected $signature = 'audit-log:digest';

    protected $description = 'Record a signed digest of the current audit chain (head, count, span)';

    public function handle(DigestRecorder $recorder, IntegritySigner $signer): int
    {
        $digest = $recorder->record();

        $count = (int) $digest->getAttribute('record_count');
        $this->info("Digest recorded over {$count} record(s).");

        if (! $signer->canSign()) {
            $this->warn('No signing key configured (audit-log.integrity.signing.private_key); digest stored unsigned.');
        }

        return self::SUCCESS;
    }
}
