@extends('audit-log::layouts.app')

@section('title', 'Noisy writes — Yammi')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
                <i data-lucide="alert-triangle" class="text-warning text-[20px]"></i> Noisy writes
            </h1>
            <p class="text-sm text-muted-foreground mt-1">
                Updates that changed nothing real — only ignored attributes such as timestamps. These usually mean the
                same record is saved twice (a double write). Worth tracking down.
            </p>
        </div>
        <span class="text-xs text-muted-foreground tabular-nums">{{ $list->total() }} records</span>
    </div>

    @include('audit-log::partials.change-list', [
        'list' => $list,
        'emptyTitle' => 'No noisy writes',
        'emptyHint' => 'Nice — every recorded change actually changed something.',
    ])

    @push('scripts')<script>__alIcons();</script>@endpush
@endsection
