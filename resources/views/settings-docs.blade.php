@extends('audit-log::layouts.app')

@section('title', 'Documentation — Yammi')

@php
    $sections = [
        [
            'icon' => 'radar',
            'title' => 'How capture works',
            'body' => 'Every Eloquent create / update / delete / restore is recorded automatically — no traits, no per-model setup. The write path is fail-closed: if the audit insert ever fails, the error goes to your application log and your request continues untouched. Eloquent events do not fire for mass Query-Builder updates, raw SQL or pivot sync() — record those explicitly:',
            'code' => "AuditLog::record(Order::class, \$order->id, 'updated',\n    before: ['status' => 'pending'],\n    after: ['status' => 'cancelled'],\n);",
        ],
        [
            'icon' => 'filter',
            'title' => 'Choosing what gets audited',
            'body' => 'By default everything is captured. Switch to opt-in mode (AUDIT_LOG_CAPTURE_MODE=opt_in) and only models implementing the ShouldAudit marker are recorded. Any model can narrow its own surface — excluded values never reach storage:',
            'code' => "use Yammi\\AuditLog\\Contracts\\ShouldAudit;\n\nclass Document extends Model implements ShouldAudit\n{\n    public array \$auditExclude = ['internal_notes'];\n    // or the inverse:\n    public array \$auditInclude = ['status', 'price'];\n}",
        ],
        [
            'icon' => 'users',
            'title' => 'Actors, origins and chains',
            'body' => 'Every record names who physically made the change (user, job, command, scheduler or system) and who triggered it: a job dispatched by a user keeps that user as its origin — even across a real queue, because the origin is serialized into the job payload. All changes of one request, command or job cascade share a correlation id; the trace page draws the whole cascade as a ladder, indented by how deep each job was nested. Click any actor badge to see everything that actor ever changed.',
            'code' => null,
        ],
        [
            'icon' => 'globe',
            'title' => 'Request metadata (ip, url, user agent)',
            'body' => 'Settings → General → Capture → "Request metadata". When enabled, every change captured during an HTTP request stores the client ip, the full url, the method and the user agent — open a record\'s expanded row to see them. It applies to NEW changes only (existing records stay as they are), and only to changes made over HTTP: console commands and queue workers never get synthetic metadata. It is PII — retention prunes it together with the record.',
            'code' => null,
        ],
        [
            'icon' => 'tag',
            'title' => 'Human-readable labels',
            'body' => 'A diff like user_id: 5 → 7 is useless a month later. Map foreign keys to their models and the package snapshots a label for the old and the new value at event time — it survives later edits or deletion of the referenced row. Models may expose getAuditLabel(); otherwise name / title / email is used.',
            'code' => "// config/audit-log.php\n'labels' => [\n    'map' => [\n        'user_id' => App\\Models\\User::class,\n    ],\n],",
        ],
        [
            'icon' => 'alert-triangle',
            'title' => 'Noise diagnostics',
            'body' => 'An update that only bumped ignored attributes (timestamps by default) changed nothing real — usually a double save. Such writes are recorded, flagged, counted in the nav badge and listed on the Noise page so you can hunt the double writes down. Configure the ignored attributes in Settings → General → Capture.',
            'code' => null,
        ],
        [
            'icon' => 'search',
            'title' => 'Finding things',
            'body' => 'The dashboard filters by model, event, actor type, actor name and date — plus full-text search across the stored old/new values and exact record ids. Dates default to the current month and a selection can never span more than one year. Timestamps are shown in the Display timezone from Settings (empty = your application timezone). Every entry links to its full change chain, and actor badges link to that actor\'s feed.',
            'code' => null,
        ],
        [
            'icon' => 'download',
            'title' => 'Export and the JSON API',
            'body' => 'The CSV / JSON buttons on the dashboard download the current filter result (capped at 10,000 rows and one year — nobody bulk-extracts five years of PII). For SPA admins there are JSON endpoints mirroring the facade; they are OFF by default and you must put real auth in front:',
            'code' => "# .env\nAUDIT_LOG_API_ENABLED=true\n\n// config/audit-log.php\n'api' => ['middleware' => ['api', 'auth:sanctum']],\n\nGET /audit-log/api/changes?event=updated&search=refund\nGET /audit-log/api/chain/{correlation-uuid}\nGET /audit-log/api/stats\nGET /audit-log/api/timeline?auditable_type=App\\Models\\Order&auditable_id=42",
        ],
        [
            'icon' => 'database-zap',
            'title' => 'Retention, archive and the dedicated database',
            'body' => 'Audit data is PII: records older than the retention window (Settings → General, default 180 days, minimum 7) are pruned daily. To keep a copy for compliance, archive the expiring rows to any filesystem disk (S3 included) before they go — or do both in one step. The whole audit store can also live on its own database connection; Settings → Database Connection shows both and moves the data.',
            'code' => "php artisan audit-log:archive                # NDJSON of expiring rows\nphp artisan audit-log:archive --then-prune   # archive, then delete\nphp artisan audit-log:transfer-data          # move to the dedicated DB",
        ],
        [
            'icon' => 'shield-check',
            'title' => 'Tamper evidence',
            'body' => 'Settings → General → Writing → "Hash-chain integrity". Every new record stores a sha256 hash chaining it to the previous record; editing or deleting a stored row breaks every hash after it. Verification names the first tampered record, and pruning keeps the chain verifiable by anchoring the newest pruned hash:',
            'code' => 'php artisan audit-log:verify',
        ],
        [
            'icon' => 'bell-ring',
            'title' => 'Sensitive-change alerts',
            'body' => 'Declare what counts as sensitive and the package fires the SensitiveChangeRecorded event (listen for Slack / webhooks) and mails the configured recipients the moment a matching change is recorded — automatic and manual records alike:',
            'code' => "// config/audit-log.php\n'alerts' => [\n    'rules' => [\n        ['model' => App\\Models\\User::class, 'attributes' => ['role'], 'events' => ['updated']],\n    ],\n    'mail_to' => ['security@your.app'],\n],",
        ],
        [
            'icon' => 'send',
            'title' => 'Performance: async writes',
            'body' => 'Settings → General → Writing → "Async writes". The audit insert is dispatched to the queue instead of running inside your request — while the actor, origin, correlation and redacted diff are still resolved synchronously at the moment of the change, so attribution never depends on worker state. The whole capture graph is also resolved once per request, not per change.',
            'code' => null,
        ],
        [
            'icon' => 'terminal',
            'title' => 'Embedding without this dashboard',
            'body' => 'This UI is optional (php artisan audit-log:ui enable|disable; it ships disabled). Everything it shows is available as plain data through the AuditLog facade — changes(), noise(), chain(), stats(), for(), record() — so you can render the audit log inside your own admin. Try every method live in the Facade Playground.',
            'code' => null,
        ],
        [
            'icon' => 'eye-off',
            'title' => 'Secrets and redaction',
            'body' => 'Field names containing password, token, secret, api_key, … (Settings → General → Redaction) are stored as [redacted] — including inside nested JSON values, and the redaction happens before anything touches the database. The diff is computed first, so "a secret changed" is still on record; only the values are masked.',
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

    <div class="space-y-4">
        @foreach ($sections as $section)
            <div class="rounded-xl border border-border bg-card p-5 shadow-xs min-w-0">
                <h2 class="text-sm font-semibold flex items-center gap-2 mb-2">
                    <i data-lucide="{{ $section['icon'] }}" class="text-brand text-[15px]"></i> {{ $section['title'] }}
                </h2>
                <p class="text-sm text-muted-foreground max-w-3xl leading-relaxed">{{ $section['body'] }}</p>
                @if ($section['code'] !== null)
                    <pre class="mt-3 rounded-lg border border-border bg-muted/30 p-3 text-[11px] font-mono overflow-x-auto leading-relaxed max-w-3xl"><code>{{ $section['code'] }}</code></pre>
                @endif
            </div>
        @endforeach
    </div>

    @push('scripts')<script>__alIcons();</script>@endpush
@endsection
