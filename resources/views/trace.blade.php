@extends('audit-log::layouts.app')

@section('title', 'Change chain — Yammi')

@section('content')
    <div class="mb-6">
        <a href="{{ route('audit-log.dashboard') }}" class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground mb-3">
            <i data-lucide="arrow-left" class="text-[13px]"></i> Back to log
        </a>
        <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
            <i data-lucide="git-fork" class="text-brand text-[20px]"></i> Change chain
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
            <span class="font-medium text-foreground">{{ $chain->count() }}</span> changes across
            <span class="font-medium text-foreground">{{ $chain->modelCount() }}</span> {{ \Illuminate\Support\Str::plural('model', $chain->modelCount()) }},
            started by <span class="font-medium text-foreground">{{ $chain->rootActorLabel() }}</span>
            on <span class="font-medium text-foreground">{{ $chain->rootModel() }}</span>.
        </p>
        <p class="mt-1 text-[11px] font-mono text-muted-foreground/70 break-all">{{ $chain->correlationId() }}</p>
    </div>

    <ol class="relative border-s border-border ml-3">
        @foreach ($chain->entries as $entry)
            @php
                $dots = ['created' => 'bg-success', 'updated' => 'bg-info', 'deleted' => 'bg-destructive', 'restored' => 'bg-warning'];
                $dot = $entry->isNoise() ? 'bg-warning' : ($dots[$entry->event()] ?? 'bg-muted-foreground');
            @endphp
            @php $isFocus = $focus !== null && $entry->recordId() === $focus; @endphp
            <li class="mb-3 ms-6" style="padding-left: {{ $entry->chainDepth() * 1.25 }}rem">
                <span class="absolute -start-[7px] mt-2 h-3.5 w-3.5 rounded-full ring-4 ring-background {{ $dot }}"></span>
                <div @if ($isFocus) id="al-focus-entry" @endif
                     class="rounded-xl border shadow-xs transition-colors {{ $isFocus ? 'border-brand ring-2 ring-brand/30 bg-brand/5' : ($entry->isNoise() ? 'border-warning/40 bg-warning/5' : ($loop->first ? 'border-brand/40 bg-brand/5' : 'border-border bg-card')) }}">
                    <div class="px-4 py-2.5 {{ $entry->changeCount() > 0 ? 'cursor-pointer' : '' }}"
                         @if ($entry->changeCount() > 0) onclick="__alToggleRow('al-trace-diff-{{ $loop->index }}')" @endif>
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="font-semibold text-sm">{{ $entry->model() }}</span>
                                <span class="text-[11px] font-mono text-muted-foreground">#{{ $entry->id() }}</span>
                                @include('audit-log::partials.event-badge', ['event' => $entry->event()])
                                @if ($isFocus)
                                    <span class="inline-flex items-center gap-1 rounded-md bg-brand px-2 py-0.5 text-[11px] font-semibold text-brand-foreground">
                                        <i data-lucide="map-pin" class="text-[11px]"></i> You came from here
                                    </span>
                                @endif
                                @if ($entry->isNoise())
                                    <span class="inline-flex items-center gap-1 rounded-md bg-warning/10 px-1.5 py-0.5 text-[11px] font-medium text-warning ring-1 ring-inset ring-warning/30"
                                          title="No real change — only ignored attributes (e.g. timestamps) changed. Often a double write.">
                                        <i data-lucide="alert-triangle" class="text-[11px]"></i> no-op
                                    </span>
                                @endif
                                @if ($loop->first)
                                    <span class="inline-flex items-center gap-1 rounded-md bg-brand/10 px-2 py-0.5 text-[11px] font-medium text-brand ring-1 ring-inset ring-brand/30">
                                        <i data-lucide="flag" class="text-[11px]"></i> Root
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-[11px] font-mono text-muted-foreground whitespace-nowrap">{{ $entry->occurredAt('H:i:s') }}</span>
                                @if ($entry->changeCount() > 0)
                                    <i data-lucide="chevron-down" class="text-[14px] text-muted-foreground"></i>
                                @endif
                            </div>
                        </div>
                        <div class="mt-1.5 flex items-center gap-x-4 gap-y-1 flex-wrap text-xs">
                            @include('audit-log::partials.actor-badge', ['type' => $entry->actorType(), 'label' => $entry->actorLabel()])
                            @if ($entry->originLabel())
                                <span class="inline-flex items-center gap-1 text-muted-foreground">
                                    <i data-lucide="corner-down-right" class="text-[12px] text-brand"></i> from {{ $entry->originLabel() }}
                                </span>
                            @endif
                            @if ($entry->changeCount() > 0)
                                <span class="text-[11px] text-muted-foreground font-mono truncate max-w-[60%]"
                                      title="{{ implode(', ', $entry->changedFieldNames()) }}">
                                    {{ $entry->changeCount() }} {{ \Illuminate\Support\Str::plural('field', $entry->changeCount()) }}:
                                    {{ implode(', ', array_slice($entry->changedFieldNames(), 0, 4)) }}@if ($entry->changeCount() > 4), +{{ $entry->changeCount() - 4 }}@endif
                                </span>
                            @endif
                            @if ($entry->jobsMonitorLink())
                                <a href="{{ $entry->jobsMonitorLink() }}" target="_blank" rel="noopener" onclick="event.stopPropagation()"
                                   class="inline-flex items-center gap-1 text-muted-foreground hover:text-foreground">
                                    <i data-lucide="activity" class="text-[12px]"></i> JobsMonitor
                                </a>
                            @endif
                        </div>
                    </div>
                    @if ($entry->changeCount() > 0)
                        <div id="al-trace-diff-{{ $loop->index }}" class="{{ $isFocus ? '' : 'hidden' }} border-t border-border px-4 py-3 animate-slide-down">
                            <div class="overflow-x-auto rounded-lg border border-border">
                                <table class="w-full min-w-[420px] text-xs font-mono">
                                    <thead>
                                        <tr class="bg-muted/50 text-[10px] uppercase tracking-wider text-muted-foreground text-left">
                                            <th class="px-3 py-1.5">Field</th>
                                            <th class="px-3 py-1.5">Old</th>
                                            <th class="px-3 py-1.5">New</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border">
                                        @foreach ($entry->changes() as $change)
                                            <tr>
                                                <td class="px-3 py-1.5 font-medium text-foreground">{{ $change['field'] }}</td>
                                                <td class="px-3 py-1.5 text-destructive break-all">{{ $change['old'] }}</td>
                                                <td class="px-3 py-1.5 text-success break-all">{{ $change['new'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>

    <p class="mt-2 text-[11px] text-muted-foreground">Click an entry to see its field-level changes.</p>

    @push('scripts')<script>
        __alIcons();
        (function () {
            var focus = document.getElementById('al-focus-entry');
            if (focus) { focus.scrollIntoView({ block: 'center' }); }
        })();
    </script>@endpush
@endsection
