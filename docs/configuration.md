# Configuration

[Back to README](../README.md)

Publish the config if you want to edit it in code:

```bash
php artisan vendor:publish --tag=audit-log-config
```

## Core options

```php
// config/audit-log.php
return [
    'enabled'   => env('AUDIT_LOG_ENABLED', true),
    'capture'   => ['mode' => env('AUDIT_LOG_CAPTURE_MODE', 'all')], // all | opt_in
    'retention' => ['days' => env('AUDIT_LOG_RETENTION_DAYS', 180)], // minimum 7
    'write'     => ['async' => env('AUDIT_LOG_WRITE_ASYNC', false)], // queue the insert
    'integrity' => ['enabled' => env('AUDIT_LOG_INTEGRITY', false)], // hash chain + verify
    'ui'        => [
        'enabled'    => env('AUDIT_LOG_UI_ENABLED', false),
        'middleware' => ['web', 'auth'],
    ],
    'database'  => ['connection' => env('AUDIT_LOG_DB_CONNECTION')], // optional dedicated connection
];
```

| Key | Default | Purpose |
|-----|---------|---------|
| `enabled` | `true` | Master switch for capture. |
| `capture.mode` | `all` | `all` audits every model; `opt_in` only those marked. |
| `retention.days` | `180` | Daily prune window (minimum 7, audit data is PII). |
| `write.async` | `false` | Move the insert onto the queue. |
| `integrity.enabled` | `false` | Hash-chain records and enable `verify`. |
| `ui.enabled` | `false` | Serve the dashboard at `/audit-log`. |
| `ui.middleware` | `['web','auth']` | Guards on the dashboard routes. |
| `database.connection` | `null` | Put the audit tables on their own connection. |

## Optional subsystems

These keys configure the advanced features; each is documented in its own page:

- `stream` ([Operations](operations.md)): SIEM driver, endpoint, token.
- `anomalies` ([Operations](operations.md)): thresholds, `cron`, custom `rules`.
- `alerts` ([Operations](operations.md)): mail and webhook channels.
- `tenancy.resolver` ([Compliance](compliance.md)): your `TenantResolver`.
- `labels.map` ([Provenance](provenance.md)): foreign-key columns to resolve into labels.
- `actor.impersonation_keys` ([Provenance](provenance.md)): session keys for login-as.

## Settings UI

Operational settings (retention, redaction, alert channels, anomaly thresholds, and more) can also be edited from the Settings UI without a redeploy. Resolution order:

```
stored DB row  →  config value  →  package default
```

Bootstrap-critical values and secrets stay in `config` / `.env`.

## Tables and migrations

Migrations are auto-loaded and create:

- `audit_log`: the records.
- `audit_log_settings`: stored settings for the UI.
- `audit_log_changed_keys`: the indexed changed-field names that make `field()` queries fast.
- `audit_log_digests` and `audit_log_chain_state`: used only when integrity is enabled.

All of them can live on the dedicated `database.connection`.

## Upgrading to the changed-keys index

Field-level searches use an indexed `audit_log_changed_keys` table. New writes populate it automatically. To cover records written before it existed, run once after upgrading:

```bash
php artisan audit-log:backfill-changed-keys   # chunked, resumable, safe to re-run
```
