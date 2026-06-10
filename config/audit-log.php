<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('AUDIT_LOG_ENABLED', true),

    'database' => [
        'connection' => env('AUDIT_LOG_DB_CONNECTION'),
        'table' => env('AUDIT_LOG_TABLE', 'audit_log'),
    ],

    'capture' => [
        'exclude' => [
            // Fully-qualified model classes that must never be audited.
        ],
    ],

    'redaction' => [
        'placeholder' => '[redacted]',
        'keys' => [
            'password',
            'remember_token',
            'token',
            'secret',
            'authorization',
            'api_key',
            'credit_card',
            'ssn',
        ],
    ],

    'retention' => [
        'days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 0),
    ],

    'ui' => [
        'enabled' => (bool) env('AUDIT_LOG_UI_ENABLED', true),
        'path' => env('AUDIT_LOG_UI_PATH', 'audit-log'),
        'middleware' => ['web'],
    ],
];
