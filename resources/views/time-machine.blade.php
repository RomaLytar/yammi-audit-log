@extends('audit-log::layouts.app')

@section('title', 'Time machine — Yammi')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
            <i data-lucide="calendar-clock" class="text-brand text-[20px]"></i> Time machine
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
            Pick a record and a date — see the exact attribute state it had at that moment, folded from its recorded history. Read-only: nothing is restored.
        </p>
    </div>

    <form method="GET" action="{{ route('audit-log.time-machine') }}" class="rounded-xl border border-border bg-card p-4 shadow-xs mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            @php
                $modelOptions = [];
                foreach ($models as $modelClass) { $modelOptions[$modelClass] = $modelClass; }
            @endphp
            <div class="min-w-0">
                @include('audit-log::components.select', [
                    'name' => 'type',
                    'label' => 'Model',
                    'value' => $type,
                    'options' => $modelOptions,
                    'placeholder' => 'Pick a model',
                    'autoSubmit' => false,
                ])
            </div>
            <div class="min-w-0">
                <label class="block text-[11px] font-medium text-muted-foreground mb-1">Record id</label>
                <input type="text" name="id" value="{{ $id }}" placeholder="42" maxlength="64"
                       class="al-input {{ $id !== '' ? 'al-input--active' : '' }}">
            </div>
            <div class="min-w-0">
                @include('audit-log::components.date-field', [
                    'name' => 'at',
                    'label' => 'State at the end of',
                    'value' => $at,
                ])
            </div>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-1.5 h-9 rounded-md bg-brand text-brand-foreground px-4 text-xs font-semibold hover:opacity-90 transition-opacity">
                <i data-lucide="history" class="text-[14px]"></i> Reconstruct
            </button>
        </div>
    </form>

    @if ($state === null)
        <div class="rounded-xl border border-border bg-card p-12 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <i data-lucide="calendar-clock"></i>
            </div>
            <p class="text-sm font-medium">Pick a model, an id and a date</p>
            <p class="text-xs text-muted-foreground mt-1">Tip: every row on the dashboard has a "State at this moment" shortcut that fills this form for you.</p>
        </div>
    @elseif (! $state->hasHistory())
        <div class="rounded-xl border border-border bg-card p-12 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <i data-lucide="search-x"></i>
            </div>
            <p class="text-sm font-medium">No recorded history for {{ $state->model() }} #{{ $state->id() }} up to {{ $state->at() }}</p>
            <p class="text-xs text-muted-foreground mt-1">Either the record was created later, or its changes were never captured.</p>
        </div>
    @else
        <div class="rounded-xl border border-border bg-card shadow-xs overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="font-semibold">{{ $state->model() }}</span>
                    <span class="text-[11px] font-mono text-muted-foreground">#{{ $state->id() }}</span>
                    @if ($state->existed())
                        <span class="inline-flex items-center gap-1 rounded-md bg-success/10 px-2 py-0.5 text-[11px] font-medium text-success ring-1 ring-inset ring-success/30">
                            <i data-lucide="check-circle-2" class="text-[11px]"></i> existed
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-md bg-destructive/10 px-2 py-0.5 text-[11px] font-medium text-destructive ring-1 ring-inset ring-destructive/30"
                              title="Deleted at this moment — values below are the last known before deletion.">
                            <i data-lucide="trash-2" class="text-[11px]"></i> deleted at this moment
                        </span>
                    @endif
                </div>
                <span class="text-[11px] font-mono text-muted-foreground whitespace-nowrap">as of {{ $state->at('Y-m-d H:i:s') }}</span>
            </div>

            <div class="px-5 py-3 flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-muted-foreground border-b border-border bg-muted/30">
                <span><span class="font-medium text-foreground tabular-nums">{{ $state->appliedChanges() }}</span> {{ \Illuminate\Support\Str::plural('change', $state->appliedChanges()) }} applied</span>
                @if ($state->lastChangeAt() !== null)
                    <span>last change <span class="font-medium text-foreground font-mono">{{ $state->lastChangeAt() }}</span></span>
                @endif
                <a href="{{ route('audit-log.dashboard', ['model' => $type, 'search' => $id]) }}" class="text-brand hover:underline inline-flex items-center gap-1">
                    <i data-lucide="list" class="text-[12px]"></i> View this record's changes
                </a>
            </div>

            @if ($state->truncated())
                <div class="mx-5 mt-4 flex items-center gap-2 rounded-md bg-warning/10 px-3 py-2 text-xs text-warning ring-1 ring-inset ring-warning/30">
                    <i data-lucide="alert-triangle" class="text-[13px]"></i>
                    <span>History longer than the reconstruction cap — only the oldest 1000 changes were folded; the state may be incomplete.</span>
                </div>
            @endif

            <div class="p-5 overflow-x-auto">
                <table class="w-full min-w-[420px] text-xs font-mono">
                    <thead>
                        <tr class="bg-muted/50 text-[10px] uppercase tracking-wider text-muted-foreground text-left">
                            <th class="px-3 py-1.5 rounded-l-md">Field</th>
                            <th class="px-3 py-1.5 rounded-r-md">Value at that moment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($state->rows() as $row)
                            <tr>
                                <td class="px-3 py-1.5 font-medium text-foreground whitespace-nowrap">{{ $row['field'] }}</td>
                                <td class="px-3 py-1.5 break-all">{{ $row['value'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-3 text-[11px] text-muted-foreground">
                    Only attributes that appear in the recorded history are shown; redacted values stay redacted.
                </p>
            </div>
        </div>
    @endif
@endsection
