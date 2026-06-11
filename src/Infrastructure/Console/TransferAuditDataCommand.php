<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Yammi\AuditLog\Infrastructure\Transfer\DatabaseTransferRunner;

/** @internal */
final class TransferAuditDataCommand extends Command
{
    protected $signature = 'audit-log:transfer-data
                            {--from=         : Source connection name (defaults to the application default connection)}
                            {--to=           : Destination connection name (defaults to audit-log.database.connection)}
                            {--delete-source : Drop the source tables after a successful transfer}';

    protected $description = 'Move all audit data between database connections';

    public function handle(DatabaseTransferRunner $runner, ConfigRepository $config): int
    {
        $from = $this->connectionOption('from') ?? $this->string($config->get('database.default'));
        $to = $this->connectionOption('to') ?? $this->string($config->get('audit-log.database.connection'));

        if ($from === null) {
            $this->error('No source connection. Pass --from=<connection>.');

            return self::FAILURE;
        }

        if ($to === null) {
            $this->error('No target connection. Set AUDIT_LOG_DB_CONNECTION or pass --to=<connection>.');

            return self::FAILURE;
        }

        $deleteSource = (bool) $this->option('delete-source');

        $this->info("Transferring audit data from \"{$from}\" to \"{$to}\"...");

        $result = $runner->run($from, $to, $deleteSource);

        if (! $result->ok) {
            $this->error($result->message);

            return self::FAILURE;
        }

        $this->info($result->message);

        if ($deleteSource) {
            $this->info("Source tables on \"{$from}\" have been dropped.");
        }

        return self::SUCCESS;
    }

    private function connectionOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
