<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use PDO;
use Yammi\AuditLog\Application\Action\TransferAuditDataAction;

/** @internal */
final class TransferAuditDataCommand extends Command
{
    protected $signature = 'audit-log:transfer-data
                            {--from=         : Source connection name (defaults to the application default connection)}
                            {--to=           : Destination connection name (defaults to audit-log.database.connection)}
                            {--delete-source : Drop the source table after a successful transfer}';

    protected $description = 'Move all audit records between database connections';

    private const MIGRATIONS_PATH = __DIR__.'/../../../database/migrations';

    public function handle(TransferAuditDataAction $transfer, DatabaseManager $db, ConfigRepository $config): int
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

        if ($from === $to) {
            $this->error("Source and destination are the same connection: \"{$from}\".");

            return self::FAILURE;
        }

        $this->info("Preparing destination connection \"{$to}\"...");
        $this->tryCreateDatabase($config, $to);

        try {
            $db->connection($to)->getPdo();
        } catch (Exception $exception) {
            $this->error("Cannot connect to \"{$to}\": {$exception->getMessage()}");
            $this->warn('Nothing was changed. Data stays on the current connection.');

            return self::FAILURE;
        }

        $migrationsPath = realpath(self::MIGRATIONS_PATH);

        if ($migrationsPath === false) {
            $this->error('Package migrations directory not found.');

            return self::FAILURE;
        }

        $this->info('Running package migrations on destination...');

        $exitCode = $this->call('migrate', [
            '--database' => $to,
            '--path' => $migrationsPath,
            '--realpath' => true,
            '--force' => true,
        ]);

        if ($exitCode !== self::SUCCESS) {
            $this->error('Migration failed on destination. Nothing was transferred.');

            return self::FAILURE;
        }

        $deleteSource = (bool) $this->option('delete-source');

        $this->info("Transferring audit records from \"{$from}\" to \"{$to}\"...");

        $result = $transfer($from, $to, $deleteSource);

        $this->info("Done. {$result->rowsMoved} row(s) moved.");

        if ($deleteSource) {
            $this->info("Source table on \"{$from}\" has been dropped.");
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

    private function tryCreateDatabase(ConfigRepository $config, string $connectionName): void
    {
        $settings = $config->get("database.connections.{$connectionName}");

        if (! is_array($settings)) {
            return;
        }

        try {
            match ($settings['driver'] ?? null) {
                'mysql' => $this->createMysqlDatabase($settings),
                'pgsql' => $this->createPgsqlDatabase($settings),
                'sqlite' => $this->createSqliteDatabase($settings),
                default => null,
            };
        } catch (Exception $exception) {
            $this->warn("Could not auto-create database (proceeding anyway): {$exception->getMessage()}");
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function createMysqlDatabase(array $settings): void
    {
        $name = str_replace('`', '', $this->scalarString($settings['database'] ?? ''));
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s', $this->scalarString($settings['host'] ?? ''), $this->scalarString($settings['port'] ?? '3306')),
            $this->scalarString($settings['username'] ?? ''),
            $this->scalarString($settings['password'] ?? ''),
        );
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function createPgsqlDatabase(array $settings): void
    {
        $pdo = new PDO(
            sprintf('pgsql:host=%s;port=%s;dbname=postgres', $this->scalarString($settings['host'] ?? ''), $this->scalarString($settings['port'] ?? '5432')),
            $this->scalarString($settings['username'] ?? ''),
            $this->scalarString($settings['password'] ?? ''),
        );
        $name = str_replace('"', '', $this->scalarString($settings['database'] ?? ''));
        $statement = $pdo->query('SELECT 1 FROM pg_database WHERE datname = '.$pdo->quote($name));
        $exists = $statement !== false ? $statement->fetch() : false;

        if ($exists === false) {
            $pdo->exec("CREATE DATABASE \"{$name}\"");
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function createSqliteDatabase(array $settings): void
    {
        $path = $this->scalarString($settings['database'] ?? '');

        if ($path !== '' && $path !== ':memory:') {
            touch($path);
        }
    }

    private function scalarString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
