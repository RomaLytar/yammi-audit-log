@php
    $emptyTitle = $emptyTitle ?? 'No changes recorded yet';
    $emptyHint = $emptyHint ?? 'As your models are created, updated or deleted, they appear here.';
@endphp

@if ($list->isEmpty())
    <div class="rounded-xl border border-border bg-card p-12 text-center">
        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
            <i data-lucide="{{ $list->filters()->isActive() ? 'search-x' : 'inbox' }}"></i>
        </div>
        @if ($list->filters()->isActive())
            <p class="text-sm font-medium">No changes match these filters</p>
            <p class="text-xs text-muted-foreground mt-1">
                Try widening the range or
                <a href="{{ route('audit-log.dashboard') }}" class="text-brand hover:underline">clear the filters</a>.
            </p>
        @else
            <p class="text-sm font-medium">{{ $emptyTitle }}</p>
            <p class="text-xs text-muted-foreground mt-1">{{ $emptyHint }}</p>
        @endif
    </div>
@else
    <div class="rounded-xl border border-border bg-card shadow-xs overflow-x-auto">
        <table class="w-full min-w-[680px] text-sm">
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
                @foreach ($list->entries as $entry)
                    @php $rowId = 'al-row-'.$loop->index; @endphp
                    <tr class="cursor-pointer transition-colors {{ $entry->isNoise() ? 'bg-warning/5 hover:bg-warning/10' : ($loop->odd ? 'bg-muted/30 hover:bg-accent/50' : 'bg-card hover:bg-accent/40') }}" onclick="__alToggleRow('{{ $rowId }}')">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <i data-lucide="chevron-right" class="text-[14px] text-muted-foreground"></i>
                                <div class="flex flex-col leading-tight min-w-0">
                                    <span class="font-medium truncate">{{ $entry->model() }}</span>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[11px] text-muted-foreground font-mono">#{{ $entry->id() }}</span>
                                        @if ($entry->hasChain())
                                            <a href="{{ route('audit-log.trace', ['correlation' => $entry->correlationId(), 'entry' => $entry->recordId()]) }}" onclick="event.stopPropagation()"
                                               title="Part of a chain of {{ $entry->chainSize() }} changes"
                                               class="inline-flex items-center gap-0.5 rounded bg-brand/10 px-1.5 py-0.5 text-[10px] font-medium text-brand ring-1 ring-inset ring-brand/20 hover:bg-brand/15">
                                                <i data-lucide="git-fork" class="text-[10px]"></i> {{ $entry->chainSize() }} in chain
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                @include('audit-log::partials.event-badge', ['event' => $entry->event()])
                                @if ($entry->isNoise())
                                    <span class="inline-flex items-center gap-1 rounded-md bg-warning/10 px-1.5 py-0.5 text-[10px] font-medium text-warning ring-1 ring-inset ring-warning/30"
                                          title="No real change — only ignored attributes (e.g. timestamps) changed. Often a double write.">
                                        <i data-lucide="alert-triangle" class="text-[10px]"></i> no-op
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @include('audit-log::partials.actor-badge', ['type' => $entry->actorType(), 'label' => $entry->actorLabel()])
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell text-xs">
                            @if ($entry->originLabel())
                                <span class="inline-flex items-center gap-1 text-muted-foreground" title="Triggered by {{ $entry->originLabel() }}">
                                    <i data-lucide="corner-down-right" class="text-[13px] text-brand"></i> {{ $entry->originLabel() }}
                                </span>
                            @else
                                <span class="text-muted-foreground">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell text-xs text-muted-foreground tabular-nums">
                            {{ $entry->changeCount() }} {{ \Illuminate\Support\Str::plural('field', $entry->changeCount()) }}
                        </td>
                        <td class="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap font-mono">{{ $entry->occurredAt() }}</td>
                    </tr>
                    <tr id="{{ $rowId }}" class="hidden">
                        <td colspan="6" class="px-5 py-4 bg-muted/30 animate-slide-down">
                            @if ($entry->isNoise())
                                <div class="mb-3 flex items-center gap-2 rounded-md bg-warning/10 px-3 py-2 text-xs text-warning ring-1 ring-inset ring-warning/30">
                                    <i data-lucide="alert-triangle" class="text-[13px]"></i>
                                    <span>This write changed nothing meaningful — only ignored attributes (e.g. timestamps). Likely a double update of the same record.</span>
                                </div>
                            @endif
                            <div class="mb-3 flex items-center gap-2 flex-wrap">
                                <a href="{{ route('audit-log.record', ['type' => $entry->auditableType(), 'id' => $entry->id()]) }}"
                                   class="inline-flex items-center gap-1.5 rounded-md border border-brand/30 bg-brand/10 px-2.5 py-1 text-xs font-medium text-brand hover:bg-brand/15">
                                    <i data-lucide="file-clock" class="text-[12px]"></i> Record view
                                </a>
                                <a href="{{ route('audit-log.time-machine', ['type' => $entry->auditableType(), 'id' => $entry->id(), 'at' => $entry->occurredAt('Y-m-d')]) }}"
                                   class="inline-flex items-center gap-1.5 rounded-md border border-border bg-card px-2.5 py-1 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-accent">
                                    <i data-lucide="calendar-clock" class="text-[12px]"></i> State at this moment
                                </a>
                                @if ($entry->correlationId())
                                    <a href="{{ route('audit-log.trace', ['correlation' => $entry->correlationId(), 'entry' => $entry->recordId()]) }}"
                                       class="inline-flex items-center gap-1.5 rounded-md border border-brand/30 bg-brand/10 px-2.5 py-1 text-xs font-medium text-brand hover:bg-brand/15">
                                        <i data-lucide="git-fork" class="text-[12px]"></i> View full change chain
                                    </a>
                                @endif
                                @if ($entry->jobsMonitorLink())
                                    <a href="{{ $entry->jobsMonitorLink() }}" target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-1.5 rounded-md border border-border bg-card px-2.5 py-1 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-accent">
                                        <i data-lucide="activity" class="text-[12px]"></i> Open job in JobsMonitor
                                    </a>
                                @endif
                            </div>
                            @if ($entry->originLabel())
                                <div class="mb-3 flex items-center gap-2 text-xs">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-brand/10 px-2 py-0.5 font-medium text-brand ring-1 ring-inset ring-brand/30">
                                        <i data-lucide="git-commit-horizontal" class="text-[12px]"></i> Chain
                                    </span>
                                    <span class="text-muted-foreground">
                                        <span class="font-medium text-foreground">{{ $entry->originLabel() }}</span>
                                        <i data-lucide="arrow-right" class="inline text-[12px]"></i>
                                        <span class="font-medium text-foreground">{{ $entry->actorLabel() }}</span>
                                        triggered this change
                                    </span>
                                </div>
                            @endif
                            @if ($entry->requestContext() !== [])
                                <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-muted-foreground font-mono">
                                    @foreach ($entry->requestContext() as $contextKey => $contextValue)
                                        <span class="break-all"><span class="text-foreground/70 font-sans font-medium">{{ $contextKey }}:</span> {{ $contextValue }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if ($entry->changeCount() === 0)
                                <p class="text-xs text-muted-foreground">No field-level changes recorded.</p>
                            @else
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
                                                    <td class="px-3 py-1.5 text-destructive break-all">
                                                        {{ $change['old'] }}
                                                        @if ($change['oldLabel'] !== null)
                                                            <span class="text-muted-foreground font-sans">({{ $change['oldLabel'] }})</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-1.5 text-success break-all">
                                                        {{ $change['new'] }}
                                                        @if ($change['newLabel'] !== null)
                                                            <span class="text-muted-foreground font-sans">({{ $change['newLabel'] }})</span>
                                                        @endif
                                                    </td>
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
        <span>Page {{ $list->page() }} of {{ $list->lastPage() }}</span>
        <div class="flex gap-2">
            @if ($list->page() > 1)
                <a href="{{ request()->fullUrlWithQuery(['page' => $list->page() - 1]) }}" class="inline-flex items-center gap-1 rounded-md border border-border bg-card px-3 h-8 hover:bg-accent">
                    <i data-lucide="chevron-left" class="text-[14px]"></i> Prev
                </a>
            @endif
            @if ($list->page() < $list->lastPage())
                <a href="{{ request()->fullUrlWithQuery(['page' => $list->page() + 1]) }}" class="inline-flex items-center gap-1 rounded-md border border-border bg-card px-3 h-8 hover:bg-accent">
                    Next <i data-lucide="chevron-right" class="text-[14px]"></i>
                </a>
            @endif
        </div>
    </div>
@endif
