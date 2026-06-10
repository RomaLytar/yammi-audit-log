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
            <span class="font-medium text-foreground">{{ $chain->modelCount }}</span> {{ \Illuminate\Support\Str::plural('model', $chain->modelCount) }},
            started by <span class="font-medium text-foreground">{{ $chain->rootActorLabel }}</span>
            on <span class="font-medium text-foreground">{{ $chain->rootModel }}</span>.
        </p>
        <p class="mt-1 text-[11px] font-mono text-muted-foreground/70">{{ $chain->correlationId }}</p>
    </div>

    <ol class="relative border-s border-border ml-3">
        @foreach ($chain->entries as $entry)
            @php
                $dots = ['created' => 'bg-success', 'updated' => 'bg-info', 'deleted' => 'bg-destructive', 'restored' => 'bg-warning'];
                $dot = $dots[$entry->event] ?? 'bg-muted-foreground';
            @endphp
            <li class="mb-5 ms-6">
                <span class="absolute -start-[7px] mt-1.5 h-3.5 w-3.5 rounded-full ring-4 ring-background {{ $dot }}"></span>
                <div class="rounded-xl border {{ $loop->first ? 'border-brand/40 bg-brand/5' : 'border-border bg-card' }} p-4 shadow-xs">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="font-semibold">{{ $entry->model() }}</span>
                            <span class="text-[11px] font-mono text-muted-foreground">#{{ $entry->auditableId }}</span>
                            @include('audit-log::partials.event-badge', ['event' => $entry->event])
                            @if ($loop->first)
                                <span class="inline-flex items-center gap-1 rounded-md bg-brand/10 px-2 py-0.5 text-[11px] font-medium text-brand ring-1 ring-inset ring-brand/30">
                                    <i data-lucide="flag" class="text-[11px]"></i> Root
                                </span>
                            @endif
                        </div>
                        <span class="text-[11px] font-mono text-muted-foreground whitespace-nowrap">
                            {{ \Illuminate\Support\Carbon::parse($entry->occurredAt)->format('Y-m-d H:i:s') }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center gap-4 flex-wrap text-xs">
                        @include('audit-log::partials.actor-badge', ['type' => $entry->actorType, 'label' => $entry->actorLabel])
                        @if ($entry->originLabel)
                            <span class="inline-flex items-center gap-1 text-muted-foreground">
                                <i data-lucide="corner-down-right" class="text-[12px] text-brand"></i> from {{ $entry->originLabel }}
                            </span>
                        @endif
                    </div>
                    @if (count($entry->changes) > 0)
                        <div class="mt-3 overflow-hidden rounded-lg border border-border">
                            <table class="w-full text-xs font-mono">
                                <thead>
                                    <tr class="bg-muted/50 text-[10px] uppercase tracking-wider text-muted-foreground text-left">
                                        <th class="px-3 py-1.5">Field</th>
                                        <th class="px-3 py-1.5">Old</th>
                                        <th class="px-3 py-1.5">New</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @foreach ($entry->changes as $field => $pair)
                                        @php
                                            $fmt = fn ($v) => $v === null ? '—' : (is_array($v) ? json_encode($v) : (string) $v);
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-1.5 font-medium text-foreground">{{ $field }}</td>
                                            <td class="px-3 py-1.5 text-destructive break-all">{{ $fmt($pair['old'] ?? null) }}</td>
                                            <td class="px-3 py-1.5 text-success break-all">{{ $fmt($pair['new'] ?? null) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>

    @push('scripts')<script>__alIcons();</script>@endpush
@endsection
