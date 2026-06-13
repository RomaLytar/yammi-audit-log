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
            <i data-lucide="calendar-days" class="text-brand text-[15px]"></i> Daily activity (last 30 days)
        </h2>
        @php
            $levelClasses = [
                0 => 'bg-muted',
                1 => 'bg-brand/25',
                2 => 'bg-brand/45',
                3 => 'bg-brand/70',
                4 => 'bg-brand',
            ];
        @endphp
        <div class="flex flex-wrap gap-1.5">
            @foreach ($stats->heatmapCells() as $cell)
                <div class="group relative">
                    <div class="h-5 w-5 rounded-[4px] {{ $levelClasses[$cell['level']] }} ring-1 ring-inset ring-border/40 transition-transform group-hover:scale-110"></div>
                    <div class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover:block z-20">
                        <div class="rounded-md bg-popover text-popover-foreground border border-border shadow-lg px-2.5 py-1.5 text-[11px] whitespace-nowrap">
                            <span class="font-semibold tabular-nums">{{ $cell['count'] }}</span> {{ \Illuminate\Support\Str::plural('change', $cell['count']) }}
                            <span class="text-muted-foreground">on {{ $cell['day'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-3 flex items-center gap-1.5 text-[10px] text-muted-foreground">
            Less
            @foreach ($levelClasses as $cls)
                <span class="h-3 w-3 rounded-[3px] {{ $cls }} ring-1 ring-inset ring-border/40"></span>
            @endforeach
            More
        </div>
    </div>

    <div class="rounded-xl border border-border bg-card p-5 shadow-xs mb-6">
        <h2 class="text-sm font-semibold flex items-center gap-2 mb-1">
            <i data-lucide="git-fork" class="text-brand text-[15px]"></i> Top cascades
        </h2>
        <p class="text-xs text-muted-foreground mb-4">The heaviest root actions in this range — one request, command or job chain that wrote across many models. Click to trace.</p>
        @if ($stats->cascadeRows() === [])
            <p class="text-xs text-muted-foreground">No correlated changes for these filters.</p>
        @else
            <div class="space-y-2.5">
                @foreach ($stats->cascadeRows() as $row)
                    <a href="{{ route('audit-log.trace', $row['id']) }}" class="block group">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-mono text-muted-foreground group-hover:text-brand truncate min-w-0">{{ $row['short'] }}</span>
                            <span class="text-muted-foreground tabular-nums shrink-0 ml-2">
                                <span class="font-semibold text-foreground">{{ number_format($row['writes']) }}</span> writes
                                · {{ $row['models'] }} {{ \Illuminate\Support\Str::plural('model', $row['models']) }}
                                · depth {{ $row['depth'] }}
                            </span>
                        </div>
                        <div class="h-1.5 rounded-full bg-muted overflow-hidden">
                            <div class="h-full rounded-full bg-brand/70 group-hover:bg-brand" style="width: {{ max(2, $row['percent']) }}%"></div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
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
                                    <span class="font-medium truncate min-w-0">{{ $row['label'] }}</span>
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
