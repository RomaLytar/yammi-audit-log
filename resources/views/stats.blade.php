@extends('audit-log::layouts.app')

@section('title', 'Statistics — Yammi')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
            <i data-lucide="bar-chart-3" class="text-brand text-[20px]"></i> Statistics
        </h1>
        <p class="text-sm text-muted-foreground mt-1">How the audit log grows and what it is made of. All filters apply.</p>
    </div>

    @include('audit-log::partials.filters', [
        'filters' => $stats->filters(),
        'action' => route('audit-log.stats'),
        'models' => $stats->models(),
        'actorTypes' => $stats->actorTypes(),
        'events' => $stats->events(),
    ])

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="rounded-xl border border-border bg-card p-4 shadow-xs">
            <div class="text-[11px] font-medium text-muted-foreground uppercase tracking-wider">Total records</div>
            <div class="mt-1 text-lg font-semibold tabular-nums">{{ number_format($stats->total()) }}</div>
        </div>
        <div class="rounded-xl border border-border bg-card p-4 shadow-xs">
            <div class="text-[11px] font-medium text-muted-foreground uppercase tracking-wider">Last 30 days</div>
            <div class="mt-1 text-lg font-semibold tabular-nums">{{ number_format($stats->last30Days()) }}</div>
        </div>
        <div class="rounded-xl border border-border bg-card p-4 shadow-xs">
            <div class="text-[11px] font-medium text-muted-foreground uppercase tracking-wider">Per day (avg)</div>
            <div class="mt-1 text-lg font-semibold tabular-nums">{{ $stats->perDay() }}</div>
        </div>
        <div class="rounded-xl border border-border bg-card p-4 shadow-xs">
            <div class="text-[11px] font-medium text-muted-foreground uppercase tracking-wider">Projected at retention</div>
            <div class="mt-1 text-lg font-semibold tabular-nums">{{ $stats->projectedRows() !== null ? '~'.number_format($stats->projectedRows()) : '∞' }}</div>
        </div>
    </div>

    <div class="rounded-xl border border-border bg-card p-5 shadow-xs mb-6">
        <h2 class="text-sm font-semibold flex items-center gap-2 mb-4">
            <i data-lucide="calendar-days" class="text-brand text-[15px]"></i> Daily activity (last 14 days)
        </h2>
        <div class="flex items-end gap-1.5 h-28">
            @foreach ($stats->dailyBars() as $bar)
                <div class="flex-1 flex flex-col items-center gap-1 min-w-0" title="{{ $bar['day'] }}: {{ $bar['count'] }}">
                    <span class="text-[10px] text-muted-foreground tabular-nums">{{ $bar['count'] }}</span>
                    <div class="w-full rounded-t bg-brand/70" style="height: {{ max(2, $bar['percent']) }}%"></div>
                    <span class="text-[9px] text-muted-foreground whitespace-nowrap">{{ substr($bar['day'], 5) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ([['By event', 'tag', $stats->eventRows()], ['By actor type', 'users', $stats->actorTypeRows()], ['Top models', 'boxes', $stats->modelRows()]] as [$title, $icon, $rows])
            <div class="rounded-xl border border-border bg-card p-5 shadow-xs">
                <h2 class="text-sm font-semibold flex items-center gap-2 mb-4">
                    <i data-lucide="{{ $icon }}" class="text-brand text-[15px]"></i> {{ $title }}
                </h2>
                @if ($rows === [])
                    <p class="text-xs text-muted-foreground">No data for these filters.</p>
                @else
                    <div class="space-y-2.5">
                        @foreach ($rows as $row)
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-medium truncate">{{ $row['label'] }}</span>
                                    <span class="text-muted-foreground tabular-nums shrink-0 ml-2">{{ number_format($row['count']) }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-muted overflow-hidden">
                                    <div class="h-full rounded-full bg-brand/70" style="width: {{ max(2, $row['percent']) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @push('scripts')<script>__alIcons();</script>@endpush
@endsection
