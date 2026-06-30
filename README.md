# Yammi Audit Log - Laravel Change History & Audit Trail

[![Latest Version on Packagist](https://img.shields.io/packagist/v/romalytar/yammi-audit-log-laravel.svg?v=1)](https://packagist.org/packages/romalytar/yammi-audit-log-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/romalytar/yammi-audit-log-laravel.svg?v=1)](https://packagist.org/packages/romalytar/yammi-audit-log-laravel)
[![CI](https://github.com/RomaLytar/yammi-audit-log/actions/workflows/ci.yml/badge.svg?branch=dev)](https://github.com/RomaLytar/yammi-audit-log/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/badge/coverage-95.5%25-brightgreen)](https://github.com/RomaLytar/yammi-audit-log/actions/workflows/ci.yml)
[![Tested on](https://img.shields.io/badge/tests-PHP%208.1%20%7C%208.2%20%7C%208.3-777BB4?logo=php&logoColor=white)](https://github.com/RomaLytar/yammi-audit-log/actions/workflows/ci.yml)
[![License](https://img.shields.io/packagist/l/romalytar/yammi-audit-log-laravel.svg?v=1)](https://packagist.org/packages/romalytar/yammi-audit-log-laravel)

**Change history and execution tracing for distributed, queue-heavy Laravel apps.** Every change carries:

- **Actor**: who executed it
- **Origin**: who started it
- **Correlation id**: ties the whole cascade together

That is what separates it from most audit packages, which record only *what* changed.

## Contents

- [Why this exists](#why-this-exists)
- [The provenance chain](#the-provenance-chain)
- [Zero model setup](#zero-model-setup)
- [Quickstart](#quickstart)
- [What makes it different](#what-makes-it-different)
- [How Yammi differs from traditional audit logs](#how-yammi-differs-from-traditional-audit-logs)
- [How it works](#how-it-works)
- [Requirements](#requirements)
- [Performance](#performance)
- [Advanced features](#advanced-features)
- [Security](#security)
- [Comparison](#comparison)
- [Non-goals](#non-goals)
- [Configuration](#configuration)
- [Documentation](#documentation)

## Why this exists

Most Laravel audit tools record *what* changed on a model. Real systems are distributed:

```
HTTP request  →  service  →  queue  →  job  →  model
```

By the time the row is written, the question that matters during an incident is hard to answer: *who actually triggered this change, and through what chain?* This package records the full execution context of every change, not just the final write.

## The provenance chain

A user clicks "pay". A queued job makes the write. A traditional audit log records the job (or "system") and loses the user. Yammi keeps the whole chain:

```
User: John Doe
  ↓  dispatches
Job: ProcessPayment
  ↓  dispatches
Job: ChargeOrder
  ↓  writes
Order #42   status: pending → paid

actor         ChargeOrder (job)
origin        John Doe (user)
correlation   550e8400-e29b-41d4-a716-446655440000
```

That is the moat: **actor** (who executed the change), **origin** (who started it), and a **correlation id** that ties the whole cascade together. Read more in [Provenance](docs/provenance.md).

In a production incident, that is the difference:

```
Order #42 became "paid" at 14:02.

Traditional audit log:
  actor = ChargeOrderJob               (who triggered it? unknown)

Yammi:
  actor       = ChargeOrderJob
  origin      = John Doe
  correlation = 550e8400-...
  chain       = ProcessPayment  →  ChargeOrder  →  Order #42
```

The dashboard draws that whole chain as a tree you can expand and pan, so you see at a glance who started it and which change caused the next:

![Change chain drawn as a causation tree](screenshots/trace.png)

## Zero model setup

No traits. No interfaces. No observers. No per-model registration.

```php
// Nothing added to your models. This is already audited:
User::first()->update(['name' => 'Test']);
```

Install, migrate, done. Capture is global from the first migration. The optional traits exist only for special cases (pivot writes, read access).

## Quickstart

```bash
composer require romalytar/yammi-audit-log-laravel
php artisan migrate
php artisan audit-log:ui enable          # optional dashboard at /audit-log
```

```php
User::first()->update(['name' => 'Test']);
```

Open `/audit-log`. The change is already there, with its actor, origin and correlation id filled in. Defaults are safe out of the box (UI off until you enable it, 180-day retention, secrets redacted).

![Audit log dashboard](screenshots/dashboard.png)

## What makes it different

**Core: provenance.** Actor, origin and a correlation id on every change, with no per-model setup (the chain above). This is the part that sets it apart from most audit packages.

**Optional add-ons**, each off or zero-cost until you use it: a time machine, a tamper-evident hash chain, SIEM streaming, anomaly detection, GDPR tooling and multi-tenancy. They build on the core; they are not the point of it. Details in [Advanced features](#advanced-features).

## How Yammi differs from traditional audit logs

Most audit packages answer one question:

- ✅ What changed?

Yammi answers the whole story:

- ✅ What changed?
- ✅ Who initiated it?
- ✅ Who executed it?
- ✅ Did it happen in a request, job, command or scheduler?
- ✅ Which queued jobs were part of the same workflow?
- ✅ What is the complete execution chain behind this change?

| Scenario | Yammi | Typical audit package |
|----------|-------|-----------------------|
| User updates a model | ✅ | ✅ |
| User triggers a queued job that updates a model | ✅ Origin preserved | ❌ User context lost |
| Scheduled task updates a model | ✅ Scheduler recorded | ❌ System |
| Admin impersonates a user | ✅ Both identities recorded | ❌ Current user only |
| Multi-job workflow investigation | ✅ Full trace | ❌ Individual events only |
| Incident root-cause analysis | ✅ Execution chain | ❌ Final write only |

## How it works

1. One global listener catches Eloquent's `created` / `updated` / `deleted` / `restored` events, nothing to register per model.
2. A small pipeline builds the diff, redacts secrets, resolves actor, origin and correlation, and snapshots foreign-key labels.
3. It writes one row into the `audit_log` table.

Optionally defer that write to the queue (`AUDIT_LOG_WRITE_ASYNC=true`) or move the table to a dedicated connection.

## Requirements

- PHP `^8.1`, Laravel `^9.0 || ^10 || ^11 || ^12 || ^13`, any database Laravel supports.
- Capture is built on Eloquent model events. Changes made by `Query Builder ->update()` or raw SQL are not seen automatically; record those explicitly with `AuditLog::record()`.
- Migrations create auto-loaded tables (`audit_log`, the settings table, and integrity tables when enabled), which can live on a dedicated connection. Full list in [Configuration](docs/configuration.md).

## Performance

A log package lives on your hot path, so the cost is kept deliberate:

- One insert for the record, plus a single batched insert for its changed-field index.
- No extra queries during capture, except opt-in label lookups and one chain-head select when integrity is on.
- Field searches (`field('status')`) seek an indexed table instead of scanning the JSON of every row.
- Capture is **fail-open**: a failed audit insert is logged and never blocks the host operation.
- Reads are bounded: one-year ranges, 10k-row exports, chunked retention.

![Statistics: growth, top cascades and change hotspots](screenshots/stats.png)

## Advanced features

Optional subsystems, each off or zero-cost until you use it:

- **[Capture policy & sampling](docs/governance.md)**: ignore fields, capture conditionally, sample high-churn models.
- **[Forensics](docs/forensics.md)**: time machine, tamper-evident hash chain, signed integrity digests.
- **[Operations](docs/operations.md)**: anomaly detection (and rules as code), SIEM streaming, alerts.
- **[Compliance](docs/compliance.md)**: GDPR subject reports, retention and archive, access logging, multi-tenancy.
- **[Analytics & dashboard](docs/analytics-and-dashboard.md)**: statistics, top cascades, hotspots, facades, JSON API, pivot auditing.
- **[Governance](docs/governance.md)**: an `event_version` schema contract, a fluent query DSL and value-transition search.

## Security

Defaults aim to be safe; you keep control of the trade-offs:

- **Secret redaction** runs before the diff is stored (`password`, `token`, `api_key`, and more, including nested JSON).
- **Fail-open by design**: if the audit insert fails, the error is logged and your business operation continues. For the opposite guarantee, wrap both in your own transaction.
- **UI off by default**, behind configurable middleware (`web`, `auth`), an optional Gate and a rate limit, serving its own assets (no external CDN).
- **API fails closed**: the read-only JSON API will not register without an auth guard in its middleware.
- **Input is validated and bounded**; CSV export escapes formula characters; retention is on by default because audit data is PII.

## Comparison

Existing Laravel audit packages, [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) and [owen-it/laravel-auditing](https://github.com/owen-it/laravel-auditing) among them, focus on model changes and user activity. This one focuses on queue-heavy, distributed apps that need execution traceability.

| Capability | Yammi | Spatie |
|------------|-------|--------|
| Model change history | ✅ | ✅ |
| Actor tracking | ✅ | ✅ |
| Origin survives queues | ✅ | ❌ |
| Correlation tracing | ✅ | ❌ |
| Execution chain reconstruction | ✅ | ❌ |

If your current setup covers your needs, keep it. This package earns its place when changes flow through queues and you need to trace them back to a person.

## Non-goals

Permanent boundaries. Each would force the audit log to become a *source of truth* or a *real-time system*, and that breaks the invariant that makes it safe to install: capture stays off your write path, fails open, is additive, and never changes your data.

- **No event sourcing or state replay**: the time machine is read-only forensics, not the system you rebuild your app from.
- **No backpressure engine**: sampling governs volume; broker-grade load shaping is Kafka/SaaS territory.
- **No in-package search engine**: search goes outward to your SIEM/Elastic, inward through the indexed changed-keys table.
- **No distributed observability platform**: metrics and traces at that scale belong to Datadog, Splunk or Pulse.
- **No query profiler**: we surface write-side cascades from data already captured; read-path profiling is Telescope/Pulse territory.

## Configuration

It works with zero config. The common switches:

```php
// config/audit-log.php (publish with vendor:publish --tag=audit-log-config)
'capture'   => ['mode' => env('AUDIT_LOG_CAPTURE_MODE', 'all')],  // all | opt_in
'retention' => ['days' => env('AUDIT_LOG_RETENTION_DAYS', 180)],
'write'     => ['async' => env('AUDIT_LOG_WRITE_ASYNC', false)],
'integrity' => ['enabled' => env('AUDIT_LOG_INTEGRITY', false)],
'ui'        => ['enabled' => env('AUDIT_LOG_UI_ENABLED', false)],
```

Settings are also editable from the Settings UI without a redeploy (resolution order: DB row, then config value, then package default). Full reference in [Configuration](docs/configuration.md).

## Documentation

- [Provenance](docs/provenance.md): actor, origin, correlation, impersonation, trace
- [Governance](docs/governance.md): capture policy, sampling, event_version, query DSL, value transitions
- [Forensics](docs/forensics.md): time machine, tamper evidence, signed digests
- [Operations](docs/operations.md): anomaly detection, rules as code, SIEM streaming, alerts
- [Compliance](docs/compliance.md): GDPR reports, retention, archive, access logging, multi-tenancy
- [Analytics & dashboard](docs/analytics-and-dashboard.md): statistics, top cascades, hotspots, facades, JSON API, pivot auditing
- [Configuration](docs/configuration.md): full config reference and the Settings UI

The dashboard also ships an in-app documentation page at `/audit-log/settings/docs`.

## License

MIT
