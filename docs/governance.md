# Governance: policy, sampling, schema, queries

[Back to README](../README.md)

Governance is opt-in control over what gets captured and how you query it, layered on top of the safe capture-everything default.

## Capture policy and sampling

The default is safe: capture everything. When you need to govern a model's volume, declare a policy in one place to ignore noisy fields, capture only under a condition, or sample a fraction of high-churn models.

```php
AuditLog::policy(Order::class)
    ->ignore(['updated_at'])                            // drop noisy fields
    ->when(fn ($order) => $order->tenant_id === 'acme') // capture conditionally
    ->sample(0.1);                                      // keep ~10% of the changes
```

- `ignore()` drops the named fields from the diff.
- `when()` records a change only when the predicate returns true (by tenant, environment or model state).
- `sample(rate)` keeps a fraction (0 to 1) of the changes for that model.

Sampling is decided **per correlation**, not per row, so the full history of one record within a unit of work is kept or dropped together, never left with holes. No policy means capture everything; this is additive to the per-model attribute lists.

## Record schema version

Every record carries an `event_version`, so SIEM and export consumers can rely on its layout:

```json
{ "event_version": 1, "event": "updated", "...": "..." }
```

- Stamped on write from `AuditRecord::SCHEMA_VERSION` (currently 1) and included in the JSON API and SIEM stream payloads.
- Bumped only when the stored record shape changes, so a consumer can branch on the version.
- The integrity hash covers a fixed field subset and is unaffected, so `verify` keeps working across versions.

## Fluent query builder

A discoverable, chainable way to write the same query as `changes([...])`:

```php
AuditLog::query()
    ->field('status')->from('pending')->to('paid')
    ->actorType('job')
    ->since('2026-01-01')->until('2026-02-01')
    ->get();
```

`AuditLog::query()` returns a builder that compiles to the same filter array and runs through the same parser, so there is no second query path or filter semantics. `from()` / `to()` express a value transition (after `field()`); `since()` / `until()` are the date range. `get()` returns the same `ChangeListData` as `changes()`.

## Value-transition queries

Find "who moved an order to cancelled" with one filter instead of scanning every diff:

```php
AuditLog::changes([
    'field'      => 'status',
    'value_from' => 'pending',
    'value_to'   => 'cancelled',
]);
```

`field` alone matches every record that touched the attribute; add the value pair to pin the exact old to new transition. Field names are validated to `[A-Za-z0-9_]`, so the JSON path is injection-safe. The field match seeks an indexed changed-keys table, not a full JSON scan, so it stays fast on large tables. Records written before this index existed are covered once you run:

```bash
php artisan audit-log:backfill-changed-keys   # chunked, resumable, safe to re-run
```

## Change reason

The log records what, who and when; `withReason()` adds why:

```php
AuditLog::withReason('ticket #4521', fn () => $order->update(['status' => 'refunded']));
```

Every change recorded inside the callback (captured or manual) is stamped with the reason and shown with a "why" badge on the dashboard. The reason is covered by the integrity hash chain, so it cannot be edited after the fact without breaking verification.
