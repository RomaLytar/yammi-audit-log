<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('AUDIT_LOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Dedicated database connection (optional)
    |--------------------------------------------------------------------------
    |
    | By default audit records are stored in your application's default
    | database, in the table named below. To isolate them in a separate
    | database, follow the three steps; leave connection null to skip.
    |
    | STEP 1 — add a connection block to config/database.php.
    |          Pick any key name you like (e.g. "audit", "audit_log").
    |
    |   'connections' => [
    |       // ... your existing connections ...
    |
    |       'audit' => [
    |           'driver'    => 'mysql',
    |           'host'      => env('AUDIT_LOG_DB_HOST', '127.0.0.1'),
    |           'port'      => env('AUDIT_LOG_DB_PORT', '3306'),
    |           'database'  => env('AUDIT_LOG_DB_DATABASE', 'audit_db'),
    |           'username'  => env('AUDIT_LOG_DB_USERNAME', 'root'),
    |           'password'  => env('AUDIT_LOG_DB_PASSWORD', ''),
    |           'charset'   => 'utf8mb4',
    |           'collation' => 'utf8mb4_unicode_ci',
    |           'prefix'    => '',
    |           'strict'    => true,
    |           'engine'    => null,
    |       ],
    |   ],
    |
    | STEP 2 — add the matching env variables to .env:
    |
    |   AUDIT_LOG_DB_CONNECTION=audit        ← must match the key from Step 1
    |   AUDIT_LOG_DB_HOST=127.0.0.1
    |   AUDIT_LOG_DB_DATABASE=audit_db
    |   AUDIT_LOG_DB_USERNAME=root
    |   AUDIT_LOG_DB_PASSWORD=secret
    |
    | STEP 3 — create the database, run the package migration on it and move
    |          any existing audit rows in one go:
    |
    |   php artisan audit-log:transfer-data
    |
    | To go back to the default database, remove AUDIT_LOG_DB_CONNECTION from
    | .env and run the command in reverse:
    |
    |   php artisan audit-log:transfer-data --from=audit --to=mysql --delete-source
    |
    */

    'database' => [
        'connection' => env('AUDIT_LOG_DB_CONNECTION'),
        'table' => env('AUDIT_LOG_TABLE', 'audit_log'),
    ],

    'capture' => [
        // "all" audits every model automatically; "opt_in" audits only models
        // implementing Yammi\AuditLog\Contracts\ShouldAudit. Models can also
        // narrow their own surface with public $auditInclude / $auditExclude
        // attribute lists.
        'mode' => env('AUDIT_LOG_CAPTURE_MODE', 'all'),

        // Attach request metadata (ip, url, method, user agent) to every
        // change captured during an HTTP request. Off by default — it is PII;
        // retention applies to it like to everything else.
        'request_context' => (bool) env('AUDIT_LOG_REQUEST_CONTEXT', false),

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

    // Timezone for DISPLAYED timestamps. Empty = the application timezone
    // (records are stored in it); e.g. 'Asia/Tokyo' to read local wall-clock
    // times. Invalid names fall back to UTC.
    'timezone' => env('AUDIT_LOG_TIMEZONE', ''),

    'labels' => [
        // Map foreign-key columns to the Eloquent model they reference. When a
        // mapped column appears in a diff, a human-readable label is snapshotted
        // at event time ("John Doe", not a live join), so it survives later
        // changes or deletion of the referenced row. Models may define
        // getAuditLabel(); otherwise name/title/email attributes are used.
        'map' => [
            // 'user_id' => App\Models\User::class,
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

    'write' => [
        // When enabled, the audit insert is dispatched to the queue instead of
        // running inside the host's request. The actor, origin, correlation and
        // redacted diff are still resolved synchronously at the moment of the
        // change — only the database write is deferred.
        'async' => (bool) env('AUDIT_LOG_WRITE_ASYNC', false),

        // Queue name for the deferred insert. null = the default queue.
        'queue' => env('AUDIT_LOG_WRITE_QUEUE'),
    ],

    'alerts' => [
        // Rules that fire the SensitiveChangeRecorded event (and mail the
        // recipients below) when a matching change is recorded.
        //
        //   ['model' => App\Models\User::class, 'attributes' => ['role'], 'events' => ['updated']],
        //
        // Empty attributes/events mean "any".
        'rules' => [],

        // Recipients for the built-in mail alert. Empty = event only.
        'mail_to' => [],
    ],

    'integrity' => [
        // Chain every stored record to the previous one with a sha256 hash, so
        // audit-log:verify can prove the history was not edited or thinned out.
        // Off by default: it costs one extra select per insert.
        'enabled' => (bool) env('AUDIT_LOG_INTEGRITY', false),
    ],

    'retention' => [
        // Records older than this many days are pruned daily. Values are
        // clamped to 7..9999; 0 = keep forever (audit data is PII — avoid).
        'days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 180),

        'schedule' => [
            'enabled' => (bool) env('AUDIT_LOG_RETENTION_SCHEDULE', true),
            'cron' => env('AUDIT_LOG_RETENTION_CRON', '0 3 * * *'),
        ],
    ],

    'integrations' => [
        'jobs_monitor' => [
            // Base URL (or path) of the Yammi JobsMonitor dashboard. When set,
            // job actors in the audit UI link to the monitor filtered by the
            // job class, e.g. '/jobs-monitor'. null = no links.
            'url' => env('AUDIT_LOG_JOBS_MONITOR_URL'),
        ],
    ],

    'ui' => [
        // Off by default: embed the data via the AuditLog facade, or turn the
        // bundled dashboard on with `php artisan audit-log:ui enable` (stored in
        // the settings table) or AUDIT_LOG_UI_ENABLED=true.
        'enabled' => (bool) env('AUDIT_LOG_UI_ENABLED', false),
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
