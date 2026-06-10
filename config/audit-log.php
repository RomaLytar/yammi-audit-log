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

        // Attributes dropped from every diff. When an update touches only these,
        // it is treated as a no-op and not recorded (e.g. a save that only bumped
        // the timestamps because of a double write).
        'ignore_attributes' => [
            'created_at',
            'updated_at',
        ],
    ],

    'actor' => [
        // Auth guards checked when attributing a change to a user. Empty = default guard.
        'guards' => [],
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
        // 0 = keep forever. Audit data is PII, so set a window in production.
        'days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 0),

        'schedule' => [
            'enabled' => (bool) env('AUDIT_LOG_RETENTION_SCHEDULE', true),
            'cron' => env('AUDIT_LOG_RETENTION_CRON', '0 3 * * *'),
        ],
    ],

    'ui' => [
        'enabled' => (bool) env('AUDIT_LOG_UI_ENABLED', true),
        'path' => env('AUDIT_LOG_UI_PATH', 'audit-log'),
        // The dashboard is gated to authenticated users by default.
        'middleware' => ['web', 'auth'],
        // Optional Gate ability checked before the dashboard (host-defined). When
        // set, a `can:<ability>` middleware is added. null = no extra gate.
        'gate' => env('AUDIT_LOG_UI_GATE'),
        // Rate limit for the UI routes, as "requests,minutes". Empty = no limit.
        'throttle' => env('AUDIT_LOG_UI_THROTTLE', '60,1'),
    ],
];
