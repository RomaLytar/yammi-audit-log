<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Yammi\AuditLog\Application\Service\PostmanCollectionBuilder;

/**
 * Exports the read API as a Postman collection for import.
 *
 * @internal
 */
final class PostmanCommand extends Command
{
    protected $signature = 'audit-log:postman {--output= : Write the collection to this file instead of stdout}';

    protected $description = 'Export the read API as a Postman collection (v2.1) for import.';

    public function handle(PostmanCollectionBuilder $builder, ConfigRepository $config): int
    {
        $path = $config->get('audit-log.api.path', 'audit-log/api');
        $name = $config->get('app.name', 'Laravel');

        $collection = $builder->build(
            (is_string($name) ? $name : 'Laravel').' Audit Log API',
            is_string($path) ? $path : 'audit-log/api',
        );

        $json = (string) json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $output = $this->option('output');

        if (is_string($output) && $output !== '') {
            file_put_contents($output, $json);
            $this->info('Postman collection written to '.$output.'.');

            return self::SUCCESS;
        }

        $this->line($json);

        return self::SUCCESS;
    }
}
