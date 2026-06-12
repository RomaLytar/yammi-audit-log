@extends('audit-log::layouts.app')

@section('title', 'Audit log — Yammi')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
                <i data-lucide="history" class="text-brand text-[20px]"></i> Change history
            </h1>
            <p class="text-sm text-muted-foreground mt-1">Who changed what, and when — across your models.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('audit-log.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
               class="inline-flex items-center gap-1.5 rounded-md border border-border bg-card px-3 h-8 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-accent"
               title="Export the current filter result (first 10000 rows) as CSV">
                <i data-lucide="download" class="text-[13px]"></i> CSV
            </a>
            <a href="{{ route('audit-log.export', array_merge(request()->query(), ['format' => 'json'])) }}"
               class="inline-flex items-center gap-1.5 rounded-md border border-border bg-card px-3 h-8 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-accent"
               title="Export the current filter result (first 10000 rows) as JSON">
                <i data-lucide="download" class="text-[13px]"></i> JSON
            </a>
            <span class="text-xs text-muted-foreground tabular-nums">{{ $list->total() }} records</span>
        </div>
    </div>

    @if ($list->hasFilterOptions() || $list->filters()->isActive())
        @include('audit-log::partials.filters', ['list' => $list])
    @endif

    @include('audit-log::partials.change-list', ['list' => $list])

    @push('scripts')<script>__alIcons();</script>@endpush
@endsection
