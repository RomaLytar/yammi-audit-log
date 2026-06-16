# Forensics: time machine and tamper evidence

[Back to README](../README.md)

Forensic features let you reconstruct the past and prove the history was not altered.

## Time machine

Reconstruct the exact state a record had at any past moment, folded from its diffs. It is read-only: it shows real history, it never rewrites anything.

```php
AuditLog::stateAt(Order::class, 42, '2026-03-03');
```

The dashboard has a Time machine page with the same capability, plus a "state at this moment" shortcut on every row.

## Tamper evidence (hash chain)

Hash-chain every record (SHA-256) and verify, after the fact, that nothing was edited or removed:

```bash
# AUDIT_LOG_INTEGRITY=true
php artisan audit-log:verify
```

Each record's hash covers a fixed subset of its fields plus the previous record's hash, forming a chain. Editing or deleting a stored row breaks the chain at that point. Pruning keeps a chain anchor (the newest pruned hash), so verification stays strict instead of trusting the first surviving row.

The first insert into an empty table is protected against a race by a single always-present chain-state row that writers lock, so concurrent first writers cannot fork the chain.

## Signed integrity digests

The hash chain catches edits to stored rows. A signed digest goes further: it attests the chain head, record count and time span at a moment, signed with your asymmetric key. Deleting whole segments (or the entire table) becomes detectable, and an archived digest verifies independently of the database.

```bash
php artisan audit-log:digest    # record a signed snapshot
php artisan audit-log:verify    # checks the chain AND the latest signed digest
```

Without a key the digest is skipped; `verify` still checks the chain. Schedule the digest via `integrity.digest_cron`.
