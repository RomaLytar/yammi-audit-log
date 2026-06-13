<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Account activity</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 0; padding: 2rem 1rem; background: #0b0d12; color: #e6e8ee; }
        .wrap { max-width: 760px; margin: 0 auto; }
        h1 { font-size: 1.25rem; margin: 0 0 .25rem; }
        .muted { color: #98a2b3; font-size: .85rem; }
        h2 { font-size: .95rem; margin: 2rem 0 .5rem; }
        table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        th, td { text-align: left; padding: .55rem .5rem; border-bottom: 1px solid #1d212b; vertical-align: top; }
        th { color: #98a2b3; font-weight: 600; }
        td.fields { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: #cbd2e0; }
        .event { display: inline-block; padding: .1rem .4rem; border-radius: .3rem; background: #1d212b; font-size: .72rem; }
        .empty { color: #98a2b3; font-size: .85rem; padding: .75rem 0; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Account activity</h1>
        <p class="muted">{{ $report->model() }} #{{ $report->auditableId }} — generated {{ $report->generatedAt }}</p>

        <h2>Changes to this record</h2>
        @if ($report->recordChanges === [])
            <p class="empty">No changes recorded.</p>
        @else
            <table>
                <thead><tr><th>When</th><th>Event</th><th>By</th><th>Fields</th></tr></thead>
                <tbody>
                @foreach ($report->recordChanges as $entry)
                    <tr>
                        <td class="muted">{{ $entry->occurredAt }}</td>
                        <td><span class="event">{{ $entry->event }}</span></td>
                        <td>{{ $entry->actorLabel }}</td>
                        <td class="fields">{{ implode(', ', array_keys($entry->changes)) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        <h2>Changes made by this subject</h2>
        @if ($report->actorChanges === [])
            <p class="empty">No changes made.</p>
        @else
            <table>
                <thead><tr><th>When</th><th>Event</th><th>On</th><th>Fields</th></tr></thead>
                <tbody>
                @foreach ($report->actorChanges as $entry)
                    <tr>
                        <td class="muted">{{ $entry->occurredAt }}</td>
                        <td><span class="event">{{ $entry->event }}</span></td>
                        <td>{{ $entry->model() }} #{{ $entry->auditableId }}</td>
                        <td class="fields">{{ implode(', ', array_keys($entry->changes)) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        @if ($report->truncated)
            <p class="muted" style="margin-top:1rem">Showing the most recent activity only.</p>
        @endif
    </div>
</body>
</html>
