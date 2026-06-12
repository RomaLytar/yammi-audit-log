@extends('audit-log::layouts.app')

@section('title', 'Documentation — Yammi')

@php
    $sections = [
        [
            'id' => 'capture',
            'icon' => 'radar',
            'title' => 'How capture works',
            'intro' => 'Every Eloquent change is recorded automatically — no traits, no per-model setup.',
            'points' => [
                'Captured events: created, updated, deleted, restored.',
                'Fail-closed: if the audit insert fails, the error goes to your application log and your request continues untouched.',
                'NOT captured (Eloquent events never fire): mass Query-Builder ->update(), raw SQL, pivot sync(). Record those explicitly:',
            ],
            'code' => "AuditLog::record(Order::class, \$order->id, 'updated',\n    before: ['status' => 'pending'],\n    after: ['status' => 'cancelled'],\n);",
        ],
        [
            'id' => 'scope',
            'icon' => 'filter',
            'title' => 'Choosing what gets audited',
            'intro' => 'By default everything is captured; narrow it globally or per model.',
            'points' => [
                'Opt-in mode: AUDIT_LOG_CAPTURE_MODE=opt_in — only models implementing the ShouldAudit marker are recorded.',
                'Exclude whole models via capture.exclude in the config.',
                'Per-model attribute lists — excluded values never reach the database:',
            ],
            'code' => "use Yammi\\AuditLog\\Contracts\\ShouldAudit;\n\nclass Document extends Model implements ShouldAudit\n{\n    public array \$auditExclude = ['internal_notes'];\n    // or the inverse — only these attributes are audited:\n    public array \$auditInclude = ['status', 'price'];\n}",
        ],
        [
            'id' => 'actors',
            'icon' => 'users',
            'title' => 'Actors, origins and chains',
            'intro' => 'Every record answers not only "what changed" but WHO changed it — and who started the cascade.',
            'points' => [
                'Actor types: user, job, command, scheduler, system.',
                'Origin: a job dispatched by a user keeps that user attached — even across a real queue (it is serialized into the job payload).',
                'Correlation: all changes of one request / command / job cascade share one id; the trace page draws the cascade as a ladder, indented by job nesting depth.',
                'Impersonation-proof: when an admin works as another user (login-as), the label names both — "Jane Doe (impersonated by Support Admin)". Session keys are configurable via actor.impersonation_keys; lab404/laravel-impersonate works out of the box.',
                'Click any actor badge to open everything that actor ever changed.',
            ],
            'code' => null,
        ],
        [
            'id' => 'time-machine',
            'icon' => 'calendar-clock',
            'title' => 'Time machine',
            'intro' => 'See the exact attribute state any record had at any past moment — read-only, nothing is ever restored.',
            'points' => [
                'The Time machine page folds the recorded diffs of one record, oldest first, into its state at the chosen date.',
                'Every dashboard row has a "State at this moment" shortcut that opens the page pre-filled.',
                'A deleted record shows its last-known values with a "deleted at this moment" badge.',
                'Also available as data:',
            ],
            'code' => "\$state = AuditLog::stateAt(Order::class, 42, '2026-03-03');\n\nif (\$state->existed) {\n    echo \$state->attributes['status'];\n}",
        ],
        [
            'id' => 'record-view',
            'icon' => 'file-clock',
            'title' => 'Record view',
            'intro' => 'One page per record: its full history side by side with every change connected to it.',
            'points' => [
                'Open it from any dashboard row ("Record view") or from the time machine.',
                'Related changes come from two honest sources: records changed by the SAME action (correlation chains) and diffs of other models whose <model>_id field points at this record.',
                'Also available as data:',
            ],
            'code' => "\$view = AuditLog::recordView(Order::class, 42);\n\nforeach (\$view->related as \$related) {\n    echo \"{\$related->entry->auditableType} #{\$related->entry->auditableId} via {\$related->via}\";\n}",
        ],
        [
            'id' => 'subject-report',
            'icon' => 'file-check',
            'title' => 'GDPR subject reports',
            'intro' => 'Answer a subject access request (GDPR Art. 15) with one command: every change to the record plus every change the subject made.',
            'points' => [
                'Writes NDJSON (machine-readable) or HTML (human-readable) to a filesystem disk.',
                'Values are stored after redaction, so the report never leaks secrets.',
                'The same report is available as data via AuditLog::subjectReport(User::class, 5).',
            ],
            'code' => "php artisan audit-log:subject-report \"App\\Models\\User\" 5\nphp artisan audit-log:subject-report \"App\\Models\\User\" 5 --format=html --disk=s3",
        ],
        [
            'id' => 'anomalies',
            'icon' => 'siren',
            'title' => 'Anomaly detection',
            'intro' => 'The audit log watches itself: bursts of changes, mass deletions and off-hours activity are flagged.',
            'points' => [
                'The Anomalies page in the nav runs the scan on demand for a chosen window (hour to 30 days).',
                'Three rules, tunable in Settings → General → Anomaly detection: change-burst threshold, mass-delete threshold and an off-hours range (e.g. 0,5).',
                'Each finding fires the AnomalyDetected event, mails alerts.mail_to and goes to the configured Slack / webhook channels (Settings → General → Alerts).',
                'Set the automatic scan cron there too (or anomalies.cron in the config), or run it yourself:',
            ],
            'code' => "php artisan audit-log:detect-anomalies\nphp artisan audit-log:detect-anomalies --window=1440",
        ],
        [
            'id' => 'tenancy',
            'icon' => 'building-2',
            'title' => 'Multi-tenancy',
            'intro' => 'Running a SaaS? Stamp every record with the current tenant and scope every read to it automatically.',
            'points' => [
                'Implement the TenantResolver contract and point tenancy.resolver at it — dashboard, facades, JSON API and exports only ever show the current tenant.',
                'Retention, archive, transfer and integrity verification always run across all tenants.',
                'Returning null (the default) means single-tenant: nothing changes.',
            ],
            'code' => "final class CurrentTenantResolver implements \\Yammi\\AuditLog\\Application\\Contract\\TenantResolver\n{\n    public function resolve(): ?string\n    {\n        return tenant()?->id;\n    }\n}\n\n// config/audit-log.php\n'tenancy' => ['resolver' => CurrentTenantResolver::class],",
        ],
        [
            'id' => 'request-metadata',
            'icon' => 'globe',
            'title' => 'Request metadata',
            'intro' => 'Attach ip, url, method and user agent to every change made during an HTTP request.',
            'points' => [
                'Enable: Settings → General → Capture → "Request metadata" (or AUDIT_LOG_REQUEST_CONTEXT=true).',
                'Shown in the expanded row of each record and included in the JSON export.',
                'Applies to NEW changes only; console commands and queue workers never get synthetic metadata.',
                'It is PII — retention prunes it together with the record.',
            ],
            'code' => null,
        ],
        [
            'id' => 'labels',
            'icon' => 'tag',
            'title' => 'Human-readable labels',
            'intro' => 'Show "John Doe → Jane Smith" instead of user_id: 5 → 7.',
            'points' => [
                'Labels are snapshotted at event time — they survive later edits or deletion of the referenced row.',
                'Models may expose getAuditLabel(); otherwise name / title / email is used.',
            ],
            'code' => "// config/audit-log.php\n'labels' => [\n    'map' => [\n        'user_id' => App\\Models\\User::class,\n    ],\n],",
        ],
        [
            'id' => 'noise',
            'icon' => 'alert-triangle',
            'title' => 'Noise diagnostics',
            'intro' => 'Updates that changed nothing real — usually a double save — are recorded and flagged.',
            'points' => [
                'An update touching only ignored attributes (timestamps by default) is marked as noise.',
                'The Noise page lists them and the nav badge counts them, so double writes are easy to hunt down.',
                'Configure the ignored attributes in Settings → General → Capture.',
            ],
            'code' => null,
        ],
        [
            'id' => 'search',
            'icon' => 'search',
            'title' => 'Finding things',
            'intro' => 'Filters, full-text search and bounded date ranges on the dashboard.',
            'points' => [
                'Filter by model, event, actor type, actor name and dates.',
                'Search matches the stored old/new values and exact record ids.',
                'Dates default to the current month; a selection can never span more than one year.',
                'Timestamps are displayed in the Settings → Dashboard → "Display timezone" (empty = your application timezone).',
            ],
            'code' => null,
        ],
        [
            'id' => 'export',
            'icon' => 'download',
            'title' => 'Export (CSV / JSON)',
            'intro' => 'The CSV and JSON buttons on the dashboard download the current filter result.',
            'points' => [
                'Capped at 10,000 rows and at most one year of data — nobody bulk-extracts five years of PII.',
                'CSV cells are hardened against spreadsheet formula injection.',
            ],
            'code' => null,
        ],
        [
            'id' => 'api',
            'icon' => 'plug',
            'title' => 'JSON API',
            'intro' => 'The same data the dashboard shows, as JSON endpoints — for SPA admins that cannot call PHP.',
            'points' => [
                'OFF by default. When enabling, put real auth into the middleware — the endpoints expose audit data.',
                'Endpoints accept the same filter query parameters as the facade (model, event, actor_type, actor, from, to, search, page).',
            ],
            'code' => "# .env\nAUDIT_LOG_API_ENABLED=true\n\n// config/audit-log.php\n'api' => ['middleware' => ['api', 'auth:sanctum']],\n\nGET /audit-log/api/changes?event=updated&search=refund\nGET /audit-log/api/noise\nGET /audit-log/api/chain/{correlation-uuid}\nGET /audit-log/api/stats\nGET /audit-log/api/timeline?auditable_type=App\\Models\\Order&auditable_id=42\nGET /audit-log/api/state?auditable_type=App\\Models\\Order&auditable_id=42&at=2026-03-03\nGET /audit-log/api/record-view?auditable_type=App\\Models\\Order&auditable_id=42\nGET /audit-log/api/subject-report?auditable_type=App\\Models\\User&auditable_id=5\nGET /audit-log/api/anomalies?window=1440",
        ],
        [
            'id' => 'retention',
            'icon' => 'database-zap',
            'title' => 'Retention, archive, dedicated DB',
            'intro' => 'Audit data is PII: keep it only as long as you must, and keep a copy if compliance asks.',
            'points' => [
                'Records older than the retention window (default 180 days, minimum 7) are pruned daily.',
                'Archive expiring rows to any filesystem disk (S3 included) before they go.',
                'The whole audit store can live on its own database connection — Settings → Database Connection shows both and moves the data.',
            ],
            'code' => "php artisan audit-log:archive                # NDJSON of expiring rows\nphp artisan audit-log:archive --then-prune   # archive, then delete\nphp artisan audit-log:transfer-data          # move to the dedicated DB",
        ],
        [
            'id' => 'integrity',
            'icon' => 'shield-check',
            'title' => 'Tamper evidence',
            'intro' => 'Prove the history was not edited: every record is hash-chained to the previous one.',
            'points' => [
                'Enable: Settings → General → Writing → "Hash-chain integrity" (or AUDIT_LOG_INTEGRITY=true).',
                'Editing or deleting a stored row breaks every hash after it; verification names the first tampered record.',
                'Pruning anchors the newest pruned hash, so the chain stays verifiable after retention runs.',
            ],
            'code' => 'php artisan audit-log:verify',
        ],
        [
            'id' => 'alerts',
            'icon' => 'bell-ring',
            'title' => 'Sensitive-change alerts',
            'intro' => 'Hear about a role change the moment it happens, not in next week\'s review.',
            'points' => [
                'Declare rules: model, optionally attributes and events (empty = any).',
                'A match fires the SensitiveChangeRecorded event, mails the recipients, posts to Slack (Block Kit with a deep link to the filtered feed) and calls a generic JSON webhook signed with HMAC-SHA256 (X-Audit-Log-Signature).',
                'Slack / webhook URLs live in Settings → General → Alerts; the signing secret stays in the env.',
                'Automatic and manual records are both inspected; every channel is fail-soft.',
            ],
            'code' => "// config/audit-log.php\n'alerts' => [\n    'rules' => [\n        ['model' => App\\Models\\User::class, 'attributes' => ['role'], 'events' => ['updated']],\n    ],\n    'mail_to' => ['security@your.app'],\n    'slack_webhook_url' => env('AUDIT_LOG_SLACK_WEBHOOK'),\n    'webhook' => ['url' => env('AUDIT_LOG_WEBHOOK_URL'), 'secret' => env('AUDIT_LOG_WEBHOOK_SECRET')],\n],",
        ],
        [
            'id' => 'performance',
            'icon' => 'send',
            'title' => 'Performance: async writes',
            'intro' => 'Move the audit insert off your request path.',
            'points' => [
                'Enable: Settings → General → Writing → "Async writes" (+ optional queue name).',
                'The actor, origin, correlation and redacted diff are still resolved at the moment of the change — only the insert is deferred, so attribution never depends on worker state.',
            ],
            'code' => null,
        ],
        [
            'id' => 'embedding',
            'icon' => 'terminal',
            'title' => 'Embedding without this dashboard',
            'intro' => 'This UI is optional — everything it shows is available as plain data.',
            'points' => [
                'The dashboard ships disabled; toggle it with php artisan audit-log:ui enable|disable.',
                'Facade methods: for(), changes(), noise(), chain(), stats(), record() — try each one live in the Facade Playground.',
            ],
            'code' => "\$list = AuditLog::changes(['actor_type' => 'job', 'search' => 'refund']);\n\$stats = AuditLog::stats();\n\$chain = AuditLog::chain(\$entry->correlationId);",
        ],
        [
            'id' => 'redaction',
            'icon' => 'eye-off',
            'title' => 'Secrets and redaction',
            'intro' => 'Secret values never reach the database.',
            'points' => [
                'Field names containing password, token, secret, api_key, … are stored as [redacted] — including inside nested JSON values.',
                'The diff is computed first, so "a secret changed" is still on record; only the values are masked.',
                'Patterns and the placeholder are editable in Settings → General → Redaction.',
            ],
            'code' => null,
        ],
    ];
@endphp

@section('content')
    <div class="mb-6">
        <a href="{{ route('audit-log.settings') }}" class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground mb-3">
            <i data-lucide="arrow-left" class="text-[13px]"></i> Settings
        </a>
        <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
            <i data-lucide="book-open" class="text-brand text-[20px]"></i> Documentation
        </h1>
        <p class="text-sm text-muted-foreground mt-1">What the audit log records, how every feature works and how to use it.</p>
    </div>

    <div class="lg:grid lg:grid-cols-[230px_minmax(0,1fr)] lg:gap-8">
        <nav class="mb-6 lg:mb-0 lg:sticky lg:top-20 lg:self-start" aria-label="Documentation sections">
            <div class="flex flex-wrap gap-1.5 lg:flex-col lg:gap-0.5">
                @foreach ($sections as $section)
                    <a href="#{{ $section['id'] }}" data-al-doc-link="{{ $section['id'] }}"
                       class="inline-flex items-center gap-2 rounded-md px-2.5 py-1.5 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
                        <i data-lucide="{{ $section['icon'] }}" class="text-[13px] shrink-0"></i>
                        <span>{{ $section['title'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>

        <div class="space-y-6 min-w-0">
            @foreach ($sections as $section)
                <section id="{{ $section['id'] }}" data-al-doc-section class="rounded-xl border border-border bg-card p-6 shadow-xs min-w-0 scroll-mt-20">
                    <h2 class="text-base font-semibold flex items-center gap-2 mb-1.5">
                        <i data-lucide="{{ $section['icon'] }}" class="text-brand text-[17px]"></i> {{ $section['title'] }}
                    </h2>
                    <p class="text-sm text-foreground/90 mb-3 max-w-3xl leading-relaxed">{{ $section['intro'] }}</p>

                    @if ($section['points'] !== [])
                        <ul class="space-y-2 max-w-3xl">
                            @foreach ($section['points'] as $point)
                                <li class="flex items-start gap-2 text-sm text-muted-foreground leading-relaxed">
                                    <i data-lucide="check" class="text-brand text-[13px] mt-1 shrink-0"></i>
                                    <span>{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($section['code'] !== null)
                        <pre class="mt-4 rounded-lg border border-border bg-muted/30 p-4 text-[12px] font-mono overflow-x-auto leading-relaxed max-w-3xl"><code>{{ $section['code'] }}</code></pre>
                    @endif
                </section>
            @endforeach
        </div>
    </div>

    @push('scripts')
    <script>
        __alIcons();

        (function () {
            var links = {};
            document.querySelectorAll('[data-al-doc-link]').forEach(function (link) {
                links[link.getAttribute('data-al-doc-link')] = link;
            });

            var activeClasses = ['bg-brand/10', 'text-brand'];

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) { return; }

                    Object.values(links).forEach(function (link) {
                        link.classList.remove.apply(link.classList, activeClasses);
                    });

                    var link = links[entry.target.id];
                    if (link) { link.classList.add.apply(link.classList, activeClasses); }
                });
            }, { rootMargin: '-20% 0px -70% 0px' });

            document.querySelectorAll('[data-al-doc-section]').forEach(function (section) {
                observer.observe(section);
            });
        })();
    </script>
    @endpush
@endsection
