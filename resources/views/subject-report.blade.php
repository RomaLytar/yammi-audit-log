<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Subject access report — {{ $report->auditableType }} #{{ $report->auditableId }}</title>
    <style>
        body { font-family: -apple-system, 'Segoe UI', Roboto, sans-serif; color: #1a1a1a; margin: 2rem auto; max-width: 960px; padding: 0 1rem; }
        h1 { font-size: 1.3rem; }
        h2 { font-size: 1.05rem; margin-top: 2rem; border-bottom: 1px solid #ddd; padding-bottom: .35rem; }
        p.meta { color: #555; font-size: .85rem; }
        table { width: 100%; border-collapse: collapse; font-size: .8rem; margin-top: .75rem; }
        th, td { border: 1px solid #ddd; padding: .4rem .55rem; text-align: left; vertical-align: top; }
        th { background: #f5f5f5; text-transform: uppercase; font-size: .68rem; letter-spacing: .04em; }
        td.mono, span.mono { font-family: ui-monospace, Menlo, Consolas, monospace; }
        .empty { color: #777; font-style: italic; margin-top: .75rem; }
        .warn { background: #fff7e6; border: 1px solid #f0c36d; padding: .5rem .75rem; font-size: .8rem; margin-top: 1rem; }
    </style>
</head>
<body>
    <h1>Subject access report</h1>
    <p class="meta">
        Subject: <span class="mono">{{ $report->auditableType }} #{{ $report->auditableId }}</span><br>
        Generated at: <span class="mono">{{ $report->generatedAt }}</span><br>
        Source: Yammi AuditLog. Values are stored after redaction; redacted values stay redacted.
    </p>

    @if ($report->truncated)
        <div class="warn">A section hit the report cap — the report may be incomplete.</div>
    @endif

    <h2>1. Changes made to this record ({{ count($report->recordChanges) }})</h2>
    @if ($report->recordChanges === [])
        <p class="empty">No recorded changes.</p>
    @else
        <table>
            <thead>
                <tr><th>When</th><th>Event</th><th>Actor</th><th>Changed fields</th></tr>
            </thead>
            <tbody>
                @foreach ($report->recordChanges as $entry)
                    <tr>
                        <td class="mono">{{ $entry->occurredAt }}</td>
                        <td>{{ $entry->event }}</td>
                        <td>{{ $entry->actorLabel }} ({{ $entry->actorType }})</td>
                        <td class="mono">
                            @foreach ($entry->changes as $field => $pair)
                                {{ $field }}: {{ is_array($pair['old'] ?? null) ? json_encode($pair['old']) : ($pair['old'] ?? '—') }} → {{ is_array($pair['new'] ?? null) ? json_encode($pair['new']) : ($pair['new'] ?? '—') }}<br>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>2. Changes performed by this subject ({{ count($report->actorChanges) }})</h2>
    @if ($report->actorChanges === [])
        <p class="empty">No recorded changes.</p>
    @else
        <table>
            <thead>
                <tr><th>When</th><th>Record</th><th>Event</th><th>Changed fields</th></tr>
            </thead>
            <tbody>
                @foreach ($report->actorChanges as $entry)
                    <tr>
                        <td class="mono">{{ $entry->occurredAt }}</td>
                        <td class="mono">{{ $entry->auditableType }} #{{ $entry->auditableId }}</td>
                        <td>{{ $entry->event }}</td>
                        <td class="mono">{{ implode(', ', array_keys($entry->changes)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
