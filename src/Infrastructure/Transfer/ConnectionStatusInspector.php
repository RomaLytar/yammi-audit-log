<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Transfer;

use Exception;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;

/** @internal */
final class ConnectionStatusInspector
{
    public function __construct(
        private readonly ConnectionResolverInterface $db,
        private readonly ConfigRepository $config,
        private readonly string $table,
    ) {}

    public function inspect(string $name): ConnectionStatusData
    {
        $settings = $this->config->get("database.connections.{$name}");
        $settings = is_array($settings) ? $settings : [];

        $driver = is_string($settings['driver'] ?? null) ? $settings['driver'] : 'unknown';
        $database = is_scalar($settings['database'] ?? null) ? (string) $settings['database'] : 'unknown';

        if ($driver === 'sqlite') {
            $database = basename($database);
        }

        try {
            $connection = $this->db->connection($name);

            if (! $connection instanceof Connection) {
                return new ConnectionStatusData($name, $driver, $database, false, false, 0);
            }

            $connection->getPdo();

            $migrated = $connection->getSchemaBuilder()->hasTable($this->table);
            $rowCount = $migrated ? $connection->table($this->table)->count() : 0;

            return new ConnectionStatusData($name, $driver, $database, true, $migrated, $rowCount);
        } catch (Exception) {
            return new ConnectionStatusData($name, $driver, $database, false, false, 0);
        }
    }
}
