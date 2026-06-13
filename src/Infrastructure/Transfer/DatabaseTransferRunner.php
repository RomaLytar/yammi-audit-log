<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Transfer;

use Exception;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Database\ConnectionResolverInterface;
use PDO;
use Yammi\AuditLog\Application\Action\TransferAuditDataAction;

/**
 * The full transfer workflow shared by the artisan command and the dashboard:
 * prepare the destination database, run the package migrations on it, then
 * move the rows. Fails closed — a failure before the move leaves everything
 * on the source connection.
 *
 * @internal
 */
final class DatabaseTransferRunner
{
    private const MIGRATIONS_PATH = __DIR__.'/../../../database/migrations';

    public function __construct(
        private readonly TransferAuditDataAction $transfer,
        private readonly ConnectionResolverInterface $db,
        private readonly ConsoleKernel $artisan,
        private readonly ConfigRepository $config,
        private readonly ConnectionGuard $guard,
    ) {}

    public function run(string $from, string $to, bool $deleteSource): TransferRunResult
    {
        if ($from === $to) {
            return TransferRunResult::failure("Source and destination are the same connection: \"{$from}\".");
        }

        $allowed = $this->configuredConnections();

        foreach (['from' => $from, 'to' => $to] as $label => $name) {
            if (! $this->guard->allows($name, $allowed)) {
                return TransferRunResult::failure("Unknown {$label} connection: \"{$name}\". Declare it in config/database.php first.");
            }
        }

        $this->tryCreateDatabase($to);

        try {
            $connection = $this->db->connection($to);

            if (method_exists($connection, 'getPdo')) {
                $connection->getPdo();
            }
        } catch (Exception $exception) {
            return TransferRunResult::failure(
                "Cannot connect to \"{$to}\": {$exception->getMessage()}. Nothing was changed.",
            );
        }

        $migrationsPath = realpath(self::MIGRATIONS_PATH);

        if ($migrationsPath === false) {
            return TransferRunResult::failure('Package migrations directory not found.');
        }

        $exitCode = $this->artisan->call('migrate', [
            '--database' => $to,
            '--path' => $migrationsPath,
            '--realpath' => true,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            return TransferRunResult::failure('Migration failed on destination. Nothing was transferred.');
        }

        return TransferRunResult::success(($this->transfer)($from, $to, $deleteSource)->rowsMoved);
    }

    private function tryCreateDatabase(string $connectionName): void
    {
        $settings = $this->config->get("database.connections.{$connectionName}");

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
        } catch (Exception) {
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function createMysqlDatabase(array $settings): void
    {
        $name = $this->scalarString($settings['database'] ?? '');

        if (! $this->guard->isSafeDatabaseName($name)) {
            return;
        }

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
        $name = $this->scalarString($settings['database'] ?? '');

        if (! $this->guard->isSafeDatabaseName($name)) {
            return;
        }

        $pdo = new PDO(
            sprintf('pgsql:host=%s;port=%s;dbname=postgres', $this->scalarString($settings['host'] ?? ''), $this->scalarString($settings['port'] ?? '5432')),
            $this->scalarString($settings['username'] ?? ''),
            $this->scalarString($settings['password'] ?? ''),
        );
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

    /**
     * @return list<string>
     */
    private function configuredConnections(): array
    {
        $connections = $this->config->get('database.connections');

        return is_array($connections) ? array_map(strval(...), array_keys($connections)) : [];
    }
}
