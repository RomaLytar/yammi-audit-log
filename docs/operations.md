# Operations: anomaly detection, SIEM streaming, alerts

[Back to README](../README.md)

Operational features make the audit log watch itself and talk to the rest of your stack.

## Anomaly detection

The log watches itself, on demand or on a cron, and findings go to Slack, a signed webhook or mail. Built-in rules:

- **Change burst**: one actor making more than N changes in the window.
- **Mass delete**: one actor deleting more than N records.
- **Off-hours**: user activity inside a configured hour range.
- **Cascade weight**: one correlation (a single request to job to job chain) that produced an unusually large number of changes across many models. Because the audit log already knows the full execution chain, this surfaces likely write-amplification or N+1-style cascades as a side-effect signal, no profiler required.

Run it yourself or on a schedule (`anomalies.cron`):

```bash
php artisan audit-log:detect-anomalies
php artisan audit-log:detect-anomalies --window=1440
```

The Anomalies page in the dashboard runs the scan on demand for a chosen window, and every threshold is editable from Settings.

## Anomaly rules as code

For domain-specific detection, write a rule class, version it in git, unit-test it in isolation, and it runs alongside the built-ins over the same window:

```php
use Yammi\AuditLog\Application\Contract\AnomalyRule;
use Yammi\AuditLog\Application\DTO\Anomaly\AnomalyData;
use Yammi\AuditLog\Application\DTO\Anomaly\AnomalyWindow;
use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;

final class PriceDropRule implements AnomalyRule
{
    public function key(): string { return 'price_drop'; }

    /** @param list<TimelineEntryData> $entries @return list<AnomalyData> */
    public function evaluate(array $entries, AnomalyWindow $window): array
    {
        // inspect $entries, return AnomalyData findings with your own severity
    }
}

// config/audit-log.php → 'anomalies' => ['rules' => [PriceDropRule::class]]
```

Rules receive the window's changes as plain DTOs, no database, no framework. A throwing rule is isolated and does not break the scan.

## SIEM streaming

Ship every recorded change to your SIEM, off the request path, queued and fail-soft, so a slow or dead sink does not touch the host write:

```php
// config/audit-log.php → 'stream'
'enabled'  => true,
'driver'   => 'splunk',                                   // splunk | datadog | elastic | http
'endpoint' => 'https://splunk.example:8088/services/collector/event',
'token'    => env('AUDIT_LOG_STREAM_TOKEN'),
'source'   => 'orders-api',
```

The normalized event is built once and wrapped in each platform's envelope. Delivery is a queued job with one retry on 5xx.

## Alerts

Findings and sensitive-change events fire to your channels. Outbound webhooks are signed with HMAC-SHA256 so receivers can verify an alert is genuine; mail goes to `alerts.mail_to`. Configure channels under Settings or in `audit-log.alerts`.
