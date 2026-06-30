<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service;

/**
 * Builds a Postman Collection (v2.1) describing the read API, so a host can
 * import the endpoints into Postman and try them instead of hand-writing every
 * request. The collection carries a base_url and a bearer {{token}} variable and
 * a worked example of every query parameter.
 */
final class PostmanCollectionBuilder
{
    /**
     * @var list<array{name: string, method: string, path: list<string>, description: string, query: list<array{0: string, 1: string, 2: string}>}>
     */
    private const ENDPOINTS = [
        [
            'name' => 'List changes',
            'method' => 'GET',
            'path' => ['changes'],
            'description' => 'Filtered, paginated changes (the dashboard list as JSON).',
            'query' => [
                ['model', 'App\\Models\\Order', 'Filter by model class.'],
                ['event', 'updated', 'created | updated | deleted | restored'],
                ['actor_type', 'user', 'user | job | command | system'],
                ['actor', '5', 'Actor identifier.'],
                ['id', '42', 'Auditable id.'],
                ['from', '2026-01-01', 'ISO date lower bound (inclusive).'],
                ['to', '2026-12-31', 'ISO date upper bound (inclusive).'],
                ['search', 'status', 'Free-text search over changed fields.'],
                ['page', '1', 'Page number.'],
            ],
        ],
        [
            'name' => 'List noise',
            'method' => 'GET',
            'path' => ['noise'],
            'description' => 'Only the no-op writes (double saves that changed nothing).',
            'query' => [
                ['model', 'App\\Models\\Order', 'Filter by model class.'],
                ['page', '1', 'Page number.'],
            ],
        ],
        [
            'name' => 'Change chain',
            'method' => 'GET',
            'path' => ['chain', '{{correlation}}'],
            'description' => 'The full cross-model causation chain behind one correlation id.',
            'query' => [],
        ],
        [
            'name' => 'Statistics',
            'method' => 'GET',
            'path' => ['stats'],
            'description' => 'Volume, breakdowns and daily activity, narrowed by the same filters as changes.',
            'query' => [
                ['model', 'App\\Models\\Order', 'Filter by model class.'],
                ['from', '2026-01-01', 'ISO date lower bound (inclusive).'],
                ['to', '2026-12-31', 'ISO date upper bound (inclusive).'],
            ],
        ],
        [
            'name' => 'Timeline',
            'method' => 'GET',
            'path' => ['timeline'],
            'description' => "One record's own history, newest first.",
            'query' => [
                ['auditable_type', 'App\\Models\\Order', 'Required. The model class.'],
                ['auditable_id', '1', 'Required. The record id.'],
                ['limit', '50', 'Max entries (1-500).'],
            ],
        ],
        [
            'name' => 'State at',
            'method' => 'GET',
            'path' => ['state'],
            'description' => 'The state a record had at a moment, folded from its diffs.',
            'query' => [
                ['auditable_type', 'App\\Models\\Order', 'Required. The model class.'],
                ['auditable_id', '1', 'Required. The record id.'],
                ['at', '2026-03-01', 'Moment (date or datetime). Omit for now.'],
            ],
        ],
        [
            'name' => 'Record view',
            'method' => 'GET',
            'path' => ['record-view'],
            'description' => "A record's history plus changes connected through chains and references.",
            'query' => [
                ['auditable_type', 'App\\Models\\Order', 'Required. The model class.'],
                ['auditable_id', '1', 'Required. The record id.'],
            ],
        ],
        [
            'name' => 'Subject report',
            'method' => 'GET',
            'path' => ['subject-report'],
            'description' => 'GDPR subject access report: every change to the record and every change it made.',
            'query' => [
                ['auditable_type', 'App\\Models\\User', 'Required. The model class.'],
                ['auditable_id', '5', 'Required. The record id.'],
            ],
        ],
        [
            'name' => 'Anomalies',
            'method' => 'GET',
            'path' => ['anomalies'],
            'description' => 'Change bursts, mass deletions and off-hours user activity in the look-back window.',
            'query' => [
                ['window', '60', 'Look-back window in minutes (1-43200).'],
            ],
        ],
    ];

    /**
     * @return array<string, mixed>
     */
    public function build(string $title, string $apiPath): array
    {
        $base = array_values(array_filter(explode('/', trim($apiPath, '/')), static fn (string $segment): bool => $segment !== ''));

        $items = [];

        foreach (self::ENDPOINTS as $endpoint) {
            $items[] = $this->item($endpoint, $base);
        }

        return [
            'info' => [
                'name' => $title,
                'description' => 'Read API for the Yammi Audit Log. Set base_url and a bearer token, then send any request.',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [['key' => 'token', 'value' => '{{token}}', 'type' => 'string']],
            ],
            'variable' => [
                ['key' => 'base_url', 'value' => 'http://localhost', 'type' => 'string'],
                ['key' => 'token', 'value' => '', 'type' => 'string'],
                ['key' => 'correlation', 'value' => '00000000-0000-4000-8000-000000000000', 'type' => 'string'],
            ],
            'item' => $items,
        ];
    }

    /**
     * @param  array{name: string, method: string, path: list<string>, description: string, query: list<array{0: string, 1: string, 2: string}>}  $endpoint
     * @param  list<string>  $base
     * @return array<string, mixed>
     */
    private function item(array $endpoint, array $base): array
    {
        $path = [...$base, ...$endpoint['path']];

        $query = [];

        foreach ($endpoint['query'] as [$key, $value, $description]) {
            $query[] = ['key' => $key, 'value' => $value, 'description' => $description];
        }

        $raw = '{{base_url}}/'.implode('/', $path);

        if ($query !== []) {
            $raw .= '?'.implode('&', array_map(static fn (array $param): string => $param['key'].'='.rawurlencode($param['value']), $query));
        }

        $url = [
            'raw' => $raw,
            'host' => ['{{base_url}}'],
            'path' => $path,
        ];

        if ($query !== []) {
            $url['query'] = $query;
        }

        return [
            'name' => $endpoint['name'],
            'request' => [
                'method' => $endpoint['method'],
                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                'url' => $url,
                'description' => $endpoint['description'],
            ],
        ];
    }
}
