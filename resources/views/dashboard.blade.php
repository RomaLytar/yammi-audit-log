@extends('audit-log::layouts.app')

@section('title', 'Audit log — Yammi')

@section('content')
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
                <i data-lucide="history" class="text-brand text-[20px]"></i> Change history
            </h1>
            <p class="text-sm text-muted-foreground mt-1">Who changed what, and when — across your models.</p>
        </div>
        <span class="text-xs text-muted-foreground tabular-nums">{{ $list->total }} records</span>
    </div>

    @if (count($list->models) > 0 || $list->filters->isActive())
        @include('audit-log::partials.filters', ['list' => $list])
    @endif

    @include('audit-log::partials.change-list', ['list' => $list])

    @push('scripts')<script>__alIcons();</script>@endpush
@endsection
