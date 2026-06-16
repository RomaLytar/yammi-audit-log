# Compliance: GDPR, retention, access logging, multi-tenancy

[Back to README](../README.md)

Audit data is PII. These features help you hold it responsibly and answer regulators.

## GDPR subject reports

Subject access reports in one command: one file with the full history of a record plus everything the subject changed themselves (exact actor match). A direct answer to GDPR Article 15.

```bash
php artisan audit-log:subject-report "App\Models\User" 5 --format=html   # or ndjson
```

Also available as `AuditLog::subjectReport()`.

## Retention and archive

- Records older than the retention window (default 180 days, minimum 7) are pruned daily.
- Archive expiring rows to any filesystem disk (S3 included) before they go.
- The whole audit store can live on its own database connection, and Settings can move the data.

```bash
php artisan audit-log:archive                # NDJSON of expiring rows
php artisan audit-log:archive --then-prune   # archive, then delete
php artisan audit-log:transfer-data          # move to the dedicated DB
```

Pruning and archiving are chunked, so they never issue an unbounded delete.

## Secret redaction

Configured secret keys are kept out of the database. Redaction runs before the diff is stored and is recursive over nested JSON. Defaults strip `password`, `token`, `secret`, `authorization`, `api_key`, `credit_card`, `ssn`; you define your own key patterns and can supply a redaction callback.

## Access logging (who viewed a record)

Under HIPAA/GDPR, *who looked at this PII* matters as much as who changed it. Opt in per model with the `LogsAccess` trait, or record from anywhere:

```php
use Yammi\AuditLog\Concerns\LogsAccess;

class Patient extends Model { use LogsAccess; }

$patient->recordAccess();                      // event: accessed, attributed to the viewer
AuditLog::recordAccess(Patient::class, $id);   // or without the trait
```

Reads are high-volume; keep `retention_days` tight when you enable this.

## Multi-tenancy

Implement one contract and point the config at it:

```php
final class CurrentTenantResolver implements \Yammi\AuditLog\Application\Contract\Resolver\TenantResolver
{
    public function resolve(): ?string
    {
        return tenant()?->id;
    }
}

// config/audit-log.php
'tenancy' => ['resolver' => CurrentTenantResolver::class],
```

Records are stamped with the tenant at capture time (it survives the queue), every read is tenant-scoped automatically, and retention, archive and integrity verification run across all tenants. Returning `null` means single-tenant: nothing changes.

## Per-subject activity feed

Hand a user a signed, read-only "Account activity" page scoped to one subject, with no login:

```php
$url = AuditLog::activityUrl($user, $user->id, minutes: 30);
```

The link is a short-lived signed route; tampering with or removing the signature returns 403.
