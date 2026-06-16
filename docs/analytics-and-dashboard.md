# Analytics and dashboard

[Back to README](../README.md)

The dashboard is optional. Everything it shows is also available as plain DTOs through one facade and an opt-in JSON API.

## Dashboard

Enable it once:

```bash
php artisan audit-log:ui enable   # /audit-log, off by default
```

It carries the change log (filters, value-transition search), a per-record view, the trace ladder, the time machine, anomalies, noise diagnostics, statistics, and a Settings UI to tune the package without a redeploy. Assets are vendored, so it makes no external CDN request and needs no CSP exceptions.

## Statistics and analytics

The Statistics page visualizes growth, projected size at retention, a 30-day activity heatmap, and:

- **Top cascades**: the heaviest correlations in the range (writes, models spanned, nesting depth), each linking to its trace. Built from captured correlation data, no profiler.
- **Top models** and **Top fields**: the most-changed types and attributes, scoped to the current filters. Field hotspots resolve through the indexed changed-keys table rather than scanning every diff.

## Facades and the JSON API

```php
AuditLog::for(Order::class, 42);          // timeline of one record
AuditLog::chain($correlationId);          // the full cascade
AuditLog::changes(['event' => 'updated', 'actor_type' => 'job']);
AuditLog::record(...);                    // manual write for mass updates / raw SQL
AuditLog::query()->field('status')->from('pending')->to('paid')->get(); // fluent builder
```

The full surface (`stateAt`, `noise`, `stats`, `recordView`, `subjectReport`, `anomalies`, and more) and the JSON API endpoints are listed in the in-app docs at `/audit-log/settings/docs`. The JSON API is opt-in and will not register without an auth guard.

## Playground

The dashboard ships a live facade Playground: pick a method, fill the inputs, run it and see the DTO result, so you can explore the read API without writing code. Destructive methods can be put behind host-defined Gates.

## Pivot auditing (attach / detach / sync)

Eloquent fires no model events for pivot writes, so a plain `$user->roles()->sync([...])` leaves no trace. Add the `AuditsPivots` trait and use the audited wrappers, which record the before/after set through the full pipeline:

```php
use Yammi\AuditLog\Concerns\AuditsPivots;

$user->auditAttach('roles', $roleId);   // event: attached
$user->auditDetach('roles', [$a, $b]);  // event: detached
$user->auditSync('roles', [$a, $c]);    // event: synced
```

## Noise diagnostics

A no-op write (a save that changes only timestamps, or a double save) is recorded and flagged `is_noise`, with its own page and a nav counter, so you can find and remove redundant writes.
