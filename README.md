# Yammi Audit Log for Laravel

Universal change history and audit log for Laravel. It records who changed what and when across your Eloquent models, with rich actor attribution, field-level diffs, correlation chains across models, a noise diagnostic for double writes, and a timeline dashboard.

## Why

Existing audit packages answer *what* changed but rarely *who* really changed it. A status flip by a queued job, an Artisan command or the scheduler all collapse into an anonymous `null`. This package treats actor attribution as a first-class concern: user, job, command, scheduler or system — and traces a whole cascade of changes across models back to the one action that started it.

## Features

- Zero-config capture of Eloquent create/update/delete/restore — or `capture.mode = opt_in` to audit only models implementing `ShouldAudit`. Models narrow their own surface with `public array $auditInclude` / `$auditExclude`.
- Multi-level actor attribution (user / job / command / scheduler / system) with an immediate-parent origin, so a nested `job → job → job` chain shows who spawned each step.
- Correlation id per unit of work (request, command or job and the jobs it dispatches), drawn as a cross-model change chain.
- Field-level diffs with secret redaction; timestamp-only "no-op" writes are recorded but flagged as noise.
- Human-readable FK labels snapshotted at event time ("John Doe → Jane Smith" next to `user_id: 5 → 7`), surviving later edits or deletion of the referenced row — map columns in `audit-log.labels.map`, optionally expose `getAuditLabel()` on the model.
- Optional dashboard (off by default — enable with `php artisan audit-log:ui enable`): filters (model, event, actor, date), full-text search across change values, a noise page, statistics and a chain/trace view. Everything it shows is also available as facade data for your own admin.
- CSV/JSON export of the current filter result (first 10000 rows) for compliance hand-offs.
- Automatic retention pruning, on by default (180 days, configurable 7–9999; audit data is PII), plus a configurable Gate and rate limit on the UI.
- Sensitive-change alerts: declare rules (model + attributes + events) and the package fires `SensitiveChangeRecorded` and mails the configured recipients when, say, a user's role changes.
- Optional tamper evidence (`AUDIT_LOG_INTEGRITY=true`): every record is hash-chained to the previous one and `php artisan audit-log:verify` names the first edited row; pruning stores a chain anchor so verification stays strict.
- Optional async writes (`AUDIT_LOG_WRITE_ASYNC=true`): the insert is queued while actor, correlation and redaction stay resolved at the moment of the change.
- JobsMonitor bridge: set `AUDIT_LOG_JOBS_MONITOR_URL=/jobs-monitor` and every job actor links straight to the monitor — "why did this change" meets "which job did it".

## Public API

Treat these as stable; everything marked `@internal` is an implementation detail and may change.

- **Facade** — `Yammi\AuditLog\Infrastructure\Facade\AuditLog`. Everything the bundled dashboard shows is available as data, so you can embed the audit log in your own admin:
  - `AuditLog::for($model)` — one record's timeline (`TimelineData`);
  - `AuditLog::changes(['model' => ..., 'event' => ..., 'actor_type' => ..., 'actor' => ..., 'from' => ..., 'to' => ..., 'search' => ..., 'page' => ...])` — the filtered, paginated dashboard list (`ChangeListData`);
  - `AuditLog::noise([...])` — only the flagged no-op writes;
  - `AuditLog::chain($correlationId)` — the cross-model change chain (`ChainData|null`);
  - `AuditLog::stats([...])` — volume, breakdowns and daily activity (`StatsData`);
  - `AuditLog::record(...)` — record a change Eloquent events cannot see.
- **DTOs** — `TimelineData`, `TimelineEntryData`, `ChangeListData`, `ChainData`, `StatsData` (all plain `public readonly` objects).
- **Config** — `config/audit-log.php` (publish with `--tag=audit-log-config`).
- **Extension contracts** (bind your own implementation): `Application\Contract\ActorProvider`, `ActorResolver`, `ValueRedactor`, `LabelResolver`, `Clock`, `CorrelationResolver`, `AuditLogQuery`, and `Domain\Audit\Repository\AuditRecordRepository`.
- **Domain value objects/enums** for custom resolvers: `Actor`, `ActorType`, `ChangeType`, `Diff`, `AuditableReference`, `LabelSnapshot`.

## Recording changes Eloquent cannot see

Mass `->update()` / `->delete()` on a query builder, raw SQL and pivot `sync()` do not fire Eloquent model events, so they are **not captured automatically**. Record them explicitly — the manual path goes through the exact same pipeline (secret redaction, actor attribution, FK labels, correlation):

```php
use Yammi\AuditLog\Infrastructure\Facade\AuditLog;

AuditLog::record(Order::class, $order->id, 'updated',
    before: ['status' => 'pending'],
    after: ['status' => 'cancelled'],
);
```

The first argument also accepts a model instance (`AuditLog::record($order, null, 'updated', ...)`). A no-op update (identical before/after) records nothing and returns `null`.

## Dedicated database connection

By default audit records live in your app's default database (`audit_log` table, name configurable via `AUDIT_LOG_TABLE`). To isolate them in their own database: add a connection to `config/database.php`, set `AUDIT_LOG_DB_CONNECTION=<that key>` in `.env`, then run

```bash
php artisan audit-log:transfer-data
```

The command creates the database when possible, runs the package migration on the new connection and moves existing rows in chunks. Going back is the same command in reverse: `audit-log:transfer-data --from=<dedicated> --to=<default> --delete-source`. The step-by-step guide lives in `config/audit-log.php`.

## Requirements

- PHP `^8.1`
- Laravel `^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0`

## License

MIT. See [LICENSE](LICENSE).
