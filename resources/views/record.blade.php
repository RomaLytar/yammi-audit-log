@extends('audit-log::layouts.app')

@section('title', 'Record — Yammi')

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('audit-log.dashboard') }}" class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground mb-3">
                <i data-lucide="arrow-left" class="text-[13px]"></i> Back to log
            </a>
            <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
                <i data-lucide="file-clock" class="text-brand text-[20px]"></i>
                {{ $record->model() }} <span class="font-mono text-muted-foreground">#{{ $record->id() }}</span>
            </h1>
            <p class="text-sm text-muted-foreground mt-1">
                Everything about this record: its own history and the changes of other records connected to it.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('audit-log.time-machine', ['type' => $type, 'id' => $id]) }}"
               class="inline-flex items-center gap-1.5 rounded-md border border-border bg-card px-3 h-8 text-xs font-semibold text-muted-foreground hover:text-foreground hover:bg-accent">
                <i data-lucide="calendar-clock" class="text-[14px]"></i> Time machine
            </a>
            <a href="{{ route('audit-log.dashboard', ['type' => $type, 'id' => $id]) }}"
               class="inline-flex items-center gap-1.5 rounded-md border border-border bg-card px-3 h-8 text-xs font-semibold text-muted-foreground hover:text-foreground hover:bg-accent">
                <i data-lucide="list" class="text-[14px]"></i> Open in dashboard
            </a>
        </div>
    </div>

    @if ($record->isEmpty())
        <div class="rounded-xl border border-border bg-card p-12 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <i data-lucide="search-x"></i>
            </div>
            <p class="text-sm font-medium">No recorded history for {{ $record->model() }} #{{ $record->id() }}</p>
            <p class="text-xs text-muted-foreground mt-1">Either the record never changed while auditing was on, or its history was pruned.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold flex items-center gap-2 mb-3">
                    <i data-lucide="history" class="text-brand text-[15px]"></i> History
                    <span class="text-xs font-normal text-muted-foreground">— {{ $record->changeCount() }} {{ \Illuminate\Support\Str::plural('change', $record->changeCount()) }}, newest first</span>
                </h2>
                <ol class="relative border-s border-border ml-3">
                    @foreach ($record->entries() as $entry)
                        @php
                            $dots = ['created' => 'bg-success', 'updated' => 'bg-info', 'deleted' => 'bg-destructive', 'restored' => 'bg-warning'];
                        @endphp
                        <li class="mb-4 ms-6">
                            <span class="absolute -start-[7px] mt-1.5 h-3.5 w-3.5 rounded-full ring-4 ring-background {{ $dots[$entry->event()] ?? 'bg-muted-foreground' }}"></span>
                            <div class="rounded-xl border border-border bg-card p-4 shadow-xs">
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <div class="flex items-center gap-2 min-w-0">
                                        @include('audit-log::partials.event-badge', ['event' => $entry->event()])
                                        @include('audit-log::partials.actor-badge', ['type' => $entry->actorType(), 'label' => $entry->actorLabel()])
                                    </div>
                                    <span class="text-[11px] font-mono text-muted-foreground whitespace-nowrap">{{ $entry->occurredAt('Y-m-d H:i:s') }}</span>
                                </div>
                                @if ($entry->changeCount() > 0)
                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs font-mono">
                                        @foreach ($entry->changes() as $change)
                                            <span class="break-all">
                                                <span class="text-foreground font-medium">{{ $change['field'] }}:</span>
                                                <span class="text-destructive">{{ $change['old'] }}</span>
                                                <i data-lucide="arrow-right" class="inline text-[11px] text-muted-foreground"></i>
                                                <span class="text-success">{{ $change['new'] }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($entry->correlationId())
                                    <a href="{{ route('audit-log.trace', ['correlation' => $entry->correlationId(), 'entry' => $entry->recordId()]) }}"
                                       class="mt-2 inline-flex items-center gap-1 text-[11px] text-brand hover:underline">
                                        <i data-lucide="git-fork" class="text-[11px]"></i> View chain
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="min-w-0">
                <h2 class="text-sm font-semibold flex items-center gap-2 mb-3">
                    <i data-lucide="network" class="text-brand text-[15px]"></i> Related changes
                    <span class="text-xs font-normal text-muted-foreground">— same cascades, or diffs whose <span class="font-mono">{{ $record->referenceField() }}</span> points here</span>
                </h2>
                @if (! $record->hasRelated())
                    <div class="rounded-xl border border-border bg-card p-8 text-center">
                        <p class="text-sm font-medium">No related changes found</p>
                        <p class="text-xs text-muted-foreground mt-1">No other record changed in the same cascades, and no recorded diff references this record.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($record->related() as $item)
                            @php $entry = $item['entry']; @endphp
                            <div class="rounded-xl border border-border bg-card p-4 shadow-xs">
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="font-semibold">{{ $entry->model() }}</span>
                                        <span class="text-[11px] font-mono text-muted-foreground">#{{ $entry->id() }}</span>
                                        @include('audit-log::partials.event-badge', ['event' => $entry->event()])
                                        <span class="inline-flex items-center gap-1 rounded-md bg-brand/10 px-1.5 py-0.5 text-[10px] font-medium text-brand ring-1 ring-inset ring-brand/20"
                                              title="{{ $item['via'] === 'chain' ? 'Changed by the same action as this record' : 'Its diff contains '.$record->referenceField().' = '.$record->id() }}">
                                            <i data-lucide="{{ $item['viaIcon'] }}" class="text-[10px]"></i> {{ $item['viaLabel'] }}
                                        </span>
                                    </div>
                                    <span class="text-[11px] font-mono text-muted-foreground whitespace-nowrap">{{ $entry->occurredAt('Y-m-d H:i:s') }}</span>
                                </div>
                                <div class="mt-2 flex items-center gap-3 flex-wrap text-xs">
                                    @include('audit-log::partials.actor-badge', ['type' => $entry->actorType(), 'label' => $entry->actorLabel()])
                                    <a href="{{ route('audit-log.record', ['type' => $entry->auditableType(), 'id' => $entry->id()]) }}"
                                       class="inline-flex items-center gap-1 text-muted-foreground hover:text-foreground">
                                        <i data-lucide="file-clock" class="text-[12px]"></i> Record view
                                    </a>
                                    @if ($entry->correlationId())
                                        <a href="{{ route('audit-log.trace', ['correlation' => $entry->correlationId(), 'entry' => $entry->recordId()]) }}"
                                           class="inline-flex items-center gap-1 text-brand hover:underline">
                                            <i data-lucide="git-fork" class="text-[12px]"></i> View chain
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection
