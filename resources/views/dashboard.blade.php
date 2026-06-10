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
        <span class="text-xs text-muted-foreground tabular-nums">{{ $records->total() }} records</span>
    </div>

    @php $hasFilters = trim(implode('', $filters)) !== ''; @endphp

    @if (count($types) > 0 || $hasFilters)
        @include('audit-log::partials.filters')
    @endif

    @if ($records->isEmpty())
        <div class="rounded-xl border border-border bg-card p-12 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <i data-lucide="{{ $hasFilters ? 'search-x' : 'inbox' }}"></i>
            </div>
            @if ($hasFilters)
                <p class="text-sm font-medium">No changes match these filters</p>
                <p class="text-xs text-muted-foreground mt-1">
                    Try widening the range or
                    <a href="{{ route('audit-log.dashboard') }}" class="text-brand hover:underline">clear the filters</a>.
                </p>
            @else
                <p class="text-sm font-medium">No changes recorded yet</p>
                <p class="text-xs text-muted-foreground mt-1">As your models are created, updated or deleted, they appear here.</p>
            @endif
        </div>
    @else
        <div class="rounded-xl border border-border bg-card shadow-xs overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-muted/40 text-[11px] uppercase tracking-wider text-muted-foreground text-left">
                        <th class="px-4 py-2.5 font-medium">Model</th>
                        <th class="px-4 py-2.5 font-medium">Event</th>
                        <th class="px-4 py-2.5 font-medium">Actor</th>
                        <th class="px-4 py-2.5 font-medium hidden md:table-cell">Origin</th>
                        <th class="px-4 py-2.5 font-medium hidden lg:table-cell">Changes</th>
                        <th class="px-4 py-2.5 font-medium">When</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($records as $record)
                        @php
                            $parts = explode('\\', (string) $record->auditable_type);
                            $short = end($parts);
                            $changes = is_array($record->changes) ? $record->changes : [];
                            $rowId = 'al-row-'.$record->id;
                        @endphp
                        <tr class="hover:bg-accent/40 cursor-pointer" onclick="__alToggleRow('{{ $rowId }}')">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <i data-lucide="chevron-right" class="text-[14px] text-muted-foreground"></i>
                                    <div class="flex flex-col leading-tight min-w-0">
                                        <span class="font-medium truncate">{{ $short }}</span>
                                        <span class="text-[11px] text-muted-foreground font-mono">#{{ $record->auditable_id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">@include('audit-log::partials.event-badge', ['event' => $record->event])</td>
                            <td class="px-4 py-3">
                                @include('audit-log::partials.actor-badge', [
                                    'type' => $record->actor_type,
                                    'label' => $record->actor_label ?? ucfirst((string) $record->actor_type),
                                ])
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell text-xs">
                                @if ($record->origin_label)
                                    <span class="inline-flex items-center gap-1 text-muted-foreground" title="Triggered by {{ $record->origin_label }}">
                                        <i data-lucide="corner-down-right" class="text-[13px] text-brand"></i>
                                        {{ $record->origin_label }}
                                    </span>
                                @else
                                    <span class="text-muted-foreground">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell text-xs text-muted-foreground tabular-nums">
                                {{ count($changes) }} {{ \Illuminate\Support\Str::plural('field', count($changes)) }}
                            </td>
                            <td class="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap font-mono">{{ $record->occurred_at }}</td>
                        </tr>
                        <tr id="{{ $rowId }}" class="hidden">
                            <td colspan="6" class="px-5 py-4 bg-muted/30 animate-slide-down">
                                @if ($record->correlation_id)
                                    <div class="mb-3">
                                        <a href="{{ route('audit-log.trace', $record->correlation_id) }}"
                                           class="inline-flex items-center gap-1.5 rounded-md border border-brand/30 bg-brand/10 px-2.5 py-1 text-xs font-medium text-brand hover:bg-brand/15">
                                            <i data-lucide="git-fork" class="text-[12px]"></i> View full change chain
                                        </a>
                                    </div>
                                @endif
                                @if ($record->origin_label)
                                    <div class="mb-3 flex items-center gap-2 text-xs">
                                        <span class="inline-flex items-center gap-1 rounded-md bg-brand/10 px-2 py-0.5 font-medium text-brand ring-1 ring-inset ring-brand/30">
                                            <i data-lucide="git-commit-horizontal" class="text-[12px]"></i> Chain
                                        </span>
                                        <span class="text-muted-foreground">
                                            <span class="font-medium text-foreground">{{ $record->origin_label }}</span>
                                            <i data-lucide="arrow-right" class="inline text-[12px]"></i>
                                            <span class="font-medium text-foreground">{{ $record->actor_label ?? ucfirst((string) $record->actor_type) }}</span>
                                            triggered this change
                                        </span>
                                    </div>
                                @endif
                                @if (count($changes) === 0)
                                    <p class="text-xs text-muted-foreground">No field-level changes recorded.</p>
                                @else
                                    <div class="overflow-hidden rounded-lg border border-border">
                                        <table class="w-full text-xs font-mono">
                                            <thead>
                                                <tr class="bg-muted/50 text-[10px] uppercase tracking-wider text-muted-foreground text-left">
                                                    <th class="px-3 py-1.5">Field</th>
                                                    <th class="px-3 py-1.5">Old</th>
                                                    <th class="px-3 py-1.5">New</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-border">
                                                @foreach ($changes as $field => $pair)
                                                    @php
                                                        $old = is_array($pair) ? ($pair['old'] ?? null) : null;
                                                        $new = is_array($pair) ? ($pair['new'] ?? null) : null;
                                                        $fmt = fn ($v) => $v === null ? '—' : (is_array($v) ? json_encode($v) : (string) $v);
                                                    @endphp
                                                    <tr>
                                                        <td class="px-3 py-1.5 font-medium text-foreground">{{ $field }}</td>
                                                        <td class="px-3 py-1.5 text-destructive break-all">{{ $fmt($old) }}</td>
                                                        <td class="px-3 py-1.5 text-success break-all">{{ $fmt($new) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex items-center justify-between text-xs text-muted-foreground">
            <span>Page {{ $records->currentPage() }} of {{ $records->lastPage() }}</span>
            <div class="flex gap-2">
                @if ($records->previousPageUrl())
                    <a href="{{ $records->previousPageUrl() }}" class="inline-flex items-center gap-1 rounded-md border border-border bg-card px-3 h-8 hover:bg-accent">
                        <i data-lucide="chevron-left" class="text-[14px]"></i> Prev
                    </a>
                @endif
                @if ($records->nextPageUrl())
                    <a href="{{ $records->nextPageUrl() }}" class="inline-flex items-center gap-1 rounded-md border border-border bg-card px-3 h-8 hover:bg-accent">
                        Next <i data-lucide="chevron-right" class="text-[14px]"></i>
                    </a>
                @endif
            </div>
        </div>
    @endif

    @push('scripts')<script>__alIcons();</script>@endpush
@endsection
