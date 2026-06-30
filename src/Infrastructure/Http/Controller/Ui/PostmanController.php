<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller\Ui;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\JsonResponse;
use Yammi\AuditLog\Application\Service\PostmanCollectionBuilder;

/**
 * Serves the read API as a downloadable Postman collection, so a host imports
 * the endpoints instead of hand-writing them. Available only when the API is on.
 *
 * @internal
 */
final class PostmanController
{
    public function __construct(
        private readonly PostmanCollectionBuilder $builder,
        private readonly ConfigRepository $config,
    ) {}

    public function __invoke(): JsonResponse
    {
        if (! $this->enabled()) {
            abort(404);
        }

        $path = $this->config->get('audit-log.api.path', 'audit-log/api');
        $name = $this->config->get('app.name', 'Laravel');

        $collection = $this->builder->build(
            (is_string($name) ? $name : 'Laravel').' Audit Log API',
            is_string($path) ? $path : 'audit-log/api',
        );

        return new JsonResponse(
            $collection,
            200,
            ['Content-Disposition' => 'attachment; filename="audit-log-api.postman.json"'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        );
    }

    private function enabled(): bool
    {
        return (bool) $this->config->get('audit-log.api.enabled', false)
            && (bool) $this->config->get('audit-log.api.postman', true);
    }
}
