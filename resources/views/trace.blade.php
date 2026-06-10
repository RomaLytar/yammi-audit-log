@extends('audit-log::layouts.app')

@section('title', 'Change chain — Yammi')

@section('content')
    @php
        $root = $records->first();
        $modelCount = $records->pluck('auditable_type')->unique()->count();
        $rootActor = $root->actor_label ?? ucfirst((string) $root->actor_type);
        $rootModel = class_basename((string) $root->auditable_type);
    @endphp

    <div class="mb-6">
        <a href="{{ route('audit-log.dashboard') }}" class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground mb-3">
            <i data-lucide="arrow-left" class="text-[13px]"></i> Back to log
        </a>
        <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
            <i data-lucide="git-fork" class="text-brand text-[20px]"></i> Change chain
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
            <span class="font-medium text-foreground">{{ $records->count() }}</span> changes across
            <span class="font-medium text-foreground">{{ $modelCount }}</span> {{ \Illuminate\Support\Str::plural('model', $modelCount) }},
            started by <span class="font-medium text-foreground">{{ $rootActor }}</span>
            on <span class="font-medium text-foreground">{{ $rootModel }}</span>.
        </p>
        <p class="mt-1 text-[11px] font-mono text-muted-foreground/70">{{ $correlation }}</p>
    </div>

    <ol class="relative border-s border-border ml-3">
        @foreach ($records as $record)
            @php
                $parts = explode('\\', (string) $record->auditable_type);
                $short = end($parts);
                $changes = is_array($record->changes) ? $record->changes : [];
                $dots = ['created' => 'bg-success', 'updated' => 'bg-info', 'deleted' => 'bg-destructive', 'restored' => 'bg-warning'];
                $dot = $dots[$record->event] ?? 'bg-muted-foreground';
            @endphp
            <li class="mb-5 ms-6">
                <span class="absolute -start-[7px] mt-1.5 h-3.5 w-3.5 rounded-full ring-4 ring-background {{ $dot }}"></span>
                <div class="rounded-xl border {{ $loop->first ? 'border-brand/40 bg-brand/5' : 'border-border bg-card' }} p-4 shadow-xs">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="font-semibold">{{ $short }}</span>
                            <span class="text-[11px] font-mono text-muted-foreground">#{{ $record->auditable_id }}</span>
                            @include('audit-log::partials.event-badge', ['event' => $record->event])
                            @if ($loop->first)
                                <span class="inline-flex items-center gap-1 rounded-md bg-brand/10 px-2 py-0.5 text-[11px] font-medium text-brand ring-1 ring-inset ring-brand/30">
                                    <i data-lucide="flag" class="text-[11px]"></i> Root
                                </span>
                            @endif
                        </div>
                        <span class="text-[11px] font-mono text-muted-foreground whitespace-nowrap">{{ $record->occurred_at }}</span>
                    </div>
                    <div class="mt-2 flex items-center gap-4 flex-wrap text-xs">
                        @include('audit-log::partials.actor-badge', [
                            'type' => $record->actor_type,
                            'label' => $record->actor_label ?? ucfirst((string) $record->actor_type),
                        ])
                        @if ($record->origin_label)
                            <span class="inline-flex items-center gap-1 text-muted-foreground">
                                <i data-lucide="corner-down-right" class="text-[12px] text-brand"></i> from {{ $record->origin_label }}
                            </span>
                        @endif
                    </div>
                    @if (count($changes) > 0)
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($changes as $field => $pair)
                                <span class="inline-flex items-center rounded bg-muted/60 px-1.5 py-0.5 text-[11px] font-mono text-muted-foreground">{{ $field }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>

    @push('scripts')<script>__alIcons();</script>@endpush
@endsection
