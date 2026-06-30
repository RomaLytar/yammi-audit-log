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

        // Session keys that mark a login-as session (lab404/laravel-impersonate
        // stores impersonated_by). When one is present the actor label names both
        // people: "Jane Doe (impersonated by Support Admin)". Empty = off.
        'impersonation_keys' => ['impersonated_by'],
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

        // Slack incoming-webhook URL. When set, every alert and anomaly
        // summary is delivered as a Block Kit message. Empty = off.
        'slack_webhook_url' => env('AUDIT_LOG_SLACK_WEBHOOK'),

        // Generic JSON webhook for incident routers / automation hubs.
        // The body is signed with HMAC-SHA256 (X-Audit-Log-Signature)
        // when a secret is set. Empty url = off.
        'webhook' => [
            'url' => env('AUDIT_LOG_WEBHOOK_URL'),
            'secret' => env('AUDIT_LOG_WEBHOOK_SECRET'),
        ],
    ],

    'stream' => [
        // Ship every recorded change to a SIEM / log platform off the request
        // path (queued, fail-soft). Empty endpoint or enabled=false = off.
        'enabled' => env('AUDIT_LOG_STREAM_ENABLED', false),

        // splunk (HEC) | datadog | elastic | http (generic JSON sink).
        'driver' => env('AUDIT_LOG_STREAM_DRIVER', 'http'),

        // Full ingest URL: Splunk HEC collector, Datadog logs intake,
        // Elastic <index>/_doc, or any JSON endpoint for the http driver.
        'endpoint' => env('AUDIT_LOG_STREAM_ENDPOINT'),

        // Auth credential: Splunk HEC token, Datadog API key, Elastic API key
        // or a bearer token for the http driver.
        'token' => env('AUDIT_LOG_STREAM_TOKEN'),

        // Logical source/service name attached to each event where supported.
        'source' => env('AUDIT_LOG_STREAM_SOURCE', 'audit-log'),

        // Extra headers merged into every request (e.g. a proxy auth header).
        'headers' => [],

        // Queue connection/name for delivery. Empty = default queue.
        'queue' => env('AUDIT_LOG_STREAM_QUEUE'),
    ],

    'tenancy' => [
        // Class implementing Yammi\AuditLog\Application\Contract\Resolver\TenantResolver.
        // When it returns a tenant id, every new record is stamped with it and
        // every read (dashboard, facades, API, exports) is scoped to it
        // automatically. Retention, archive, transfer and integrity always run
        // across all tenants. null = single-tenant.
        'resolver' => null,
    ],

    'anomalies' => [
        // Look-back window in minutes for audit-log:detect-anomalies.
        'window_minutes' => 60,

        // Flag an actor with more changes than this inside the window. 0 = rule off.
        'rate_threshold' => 200,

        // Flag an actor deleting more records than this inside the window. 0 = rule off.
        'delete_threshold' => 25,

        // Flag a single correlation (one request -> job -> job chain) that
        // produced more than this many changes, a possible write-amplification
        // or N+1-style cascade. 0 = rule off.
        'cascade_threshold' => 150,

        // Flag user changes recorded between these hours (inclusive, 0-23),
        // e.g. [0, 5] for night activity; [22, 5] wraps midnight. Empty = rule off.
        'off_hours' => [],

        // Cron expression to run the scan automatically, e.g. '0 * * * *'.
        // Findings fire the AnomalyDetected event and mail alerts.mail_to.
        // Empty = run the command yourself.
        'cron' => env('AUDIT_LOG_ANOMALY_CRON'),

        // Detection-as-code: your own rule classes implementing
        // Yammi\AuditLog\Application\Contract\AnomalyRule. They run alongside
        // the built-in checks over the same window and can set their own
        // severity. Version them in git, unit-test them in isolation.
        //
        //   App\Audit\HighValueRefundRule::class,
        'rules' => [],
    ],

    'integrity' => [
        // Chain every stored record to the previous one with a sha256 hash, so
        // audit-log:verify can prove the history was not edited or thinned out.
        // Off by default: it costs one extra select per insert.
        'enabled' => (bool) env('AUDIT_LOG_INTEGRITY', false),

        // Asymmetric key pair (RSA/EC, PEM inline or a readable file path) used
        // to sign integrity digests. audit-log:digest records a signed snapshot
        // of the chain head + count + span; audit-log:verify checks the latest
        // one, catching whole-segment deletion and verifying archived digests
        // independently of the database. No private key = digests stored
        // unsigned. Keep the private key off the audit host where possible.
        'signing' => [
            'private_key' => env('AUDIT_LOG_SIGNING_PRIVATE_KEY'),
            'public_key' => env('AUDIT_LOG_SIGNING_PUBLIC_KEY'),
        ],

        // Cron expression to record a signed digest automatically, e.g.
        // '0 * * * *'. Empty = run audit-log:digest yourself.
        'digest_cron' => env('AUDIT_LOG_DIGEST_CRON'),
    ],

    'archive' => [
        // Disk audit-log:archive writes NDJSON exports to before retention
        // deletes the rows (s3, local, ...).
        'disk' => env('AUDIT_LOG_ARCHIVE_DISK', 'local'),
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

        'observability' => [
            // Template URL of your tracing backend (Datadog, Jaeger, Tempo,
            // Honeycomb...). When set, a chain that carried a W3C traceparent
            // shows an "Open distributed trace" link, so you jump from
            // who-changed-what to the trace that drove it. Use {trace_id} as the
            // placeholder, e.g. 'https://app.datadoghq.com/apm/trace/{trace_id}'.
            // null = show the raw id only.
            'trace_url' => env('AUDIT_LOG_TRACE_URL'),
        ],
    ],

    'api' => [
        // JSON endpoints mirroring the facade (changes, noise, chain, stats,
        // timeline) for SPA admins. Off by default; when you enable it, put
        // real auth in the middleware (e.g. ['api', 'auth:sanctum']).
        'enabled' => (bool) env('AUDIT_LOG_API_ENABLED', false),
        'path' => env('AUDIT_LOG_API_PATH', 'audit-log/api'),
        'middleware' => ['api'],

        // Fail closed: when the middleware above carries no authentication guard
        // (auth, auth:*, can:* or an Authenticate middleware) the routes are NOT
        // registered, so flipping the API on can't silently expose audit data.
        // Set this true only when auth is enforced by some unrecognised means.
        'allow_unauthenticated' => (bool) env('AUDIT_LOG_API_ALLOW_UNAUTHENTICATED', false),

        // Offer a Postman collection (v2.1) of these endpoints: a "Download
        // Postman collection" button in the dashboard and the audit-log:postman
        // command, so a host imports the API instead of hand-writing requests.
        'postman' => (bool) env('AUDIT_LOG_API_POSTMAN', true),
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

    'transfer' => [
        // Optional Gate ability checked before the dashboard "Transfer data"
        // action (host-defined). Moving and optionally deleting audit rows
        // between databases is destructive — gate it to your most privileged
        // operators. Source and destination are always restricted to the
        // connections declared in config/database.php. null = no extra gate.
        'gate' => env('AUDIT_LOG_TRANSFER_GATE'),
    ],

    'playground' => [
        // Optional Gate ability checked before destructive facade-playground
        // methods (record, recordAccess) that write audit rows. Read-only
        // methods are never gated. null = no extra gate.
        'gate' => env('AUDIT_LOG_PLAYGROUND_GATE'),
    ],
];
