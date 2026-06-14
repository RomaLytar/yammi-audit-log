@php
    $filters = isset($list) ? $list->filters() : $filters;
    $action = $action ?? route('audit-log.dashboard');

    $typeOptions = ['' => 'All models'];
    foreach (isset($list) ? $list->models() : ($models ?? []) as $model) {
        $typeOptions[$model] = class_basename($model);
    }

    $eventOptions = ['' => 'All events'];
    foreach (isset($list) ? $list->events() : ($events ?? []) as $event) {
        $eventOptions[$event] = ucfirst($event);
    }

    $actorOptions = ['' => 'All actors'];
    foreach (isset($list) ? $list->actorTypes() : ($actorTypes ?? []) as $actorType) {
        $actorOptions[$actorType] = ucfirst($actorType);
    }
@endphp
<form method="GET" action="{{ $action }}" class="mb-5 rounded-xl border border-border bg-card p-4 shadow-xs">
    <div class="flex items-center justify-between mb-3">
        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
            <i data-lucide="sliders-horizontal" class="text-[14px]"></i> Filters
            <span class="text-[10px] text-muted-foreground/70">(applied automatically)</span>
        </span>
        @if ($filters->isActive())
            <a href="{{ $action }}" class="inline-flex items-center gap-1.5 rounded-md border border-border bg-card px-3 h-8 text-xs font-medium hover:bg-accent">
                <i data-lucide="x" class="text-[13px]"></i> Clear
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 mb-3">
        <div class="sm:col-span-2 lg:col-span-6">
            <label class="block text-[11px] font-medium text-muted-foreground mb-1">Search changes</label>
            <input type="text" name="q" value="{{ $filters->search }}" placeholder="Find by old/new value or record id…"
                   onchange="this.form && this.form.requestSubmit()"
                   class="al-input {{ $filters->search !== '' ? 'al-input--active' : '' }}">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        @include('audit-log::components.select', ['name' => 'type', 'label' => 'Model', 'options' => $typeOptions, 'value' => $filters->type, 'placeholder' => 'All models'])
        @include('audit-log::components.select', ['name' => 'event', 'label' => 'Event', 'options' => $eventOptions, 'value' => $filters->event, 'placeholder' => 'All events'])
        @include('audit-log::components.select', ['name' => 'actor_type', 'label' => 'Actor type', 'options' => $actorOptions, 'value' => $filters->actorType, 'placeholder' => 'All actors'])
        <div>
            <label class="block text-[11px] font-medium text-muted-foreground mb-1">Actor name</label>
            <input type="text" name="actor" value="{{ $filters->actor }}" placeholder="e.g. John Doe"
                   onchange="this.form && this.form.requestSubmit()"
                   class="al-input {{ $filters->actor !== '' ? 'al-input--active' : '' }}">
        </div>
        @include('audit-log::components.date-field', ['name' => 'from', 'label' => 'From', 'value' => $filters->from, 'max' => $filters->to])
        @include('audit-log::components.date-field', ['name' => 'to', 'label' => 'To', 'value' => $filters->to, 'min' => $filters->from])
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 mt-3">
        <div class="lg:col-span-2">
            <label class="block text-[11px] font-medium text-muted-foreground mb-1">Field changed</label>
            <input type="text" name="field" value="{{ $filters->field }}" placeholder="e.g. status"
                   onchange="this.form && this.form.requestSubmit()"
                   class="al-input {{ $filters->field !== '' ? 'al-input--active' : '' }}">
        </div>
        <div class="lg:col-span-2">
            <label class="block text-[11px] font-medium text-muted-foreground mb-1">From value</label>
            <input type="text" name="value_from" value="{{ $filters->valueFrom }}" placeholder="e.g. pending"
                   onchange="this.form && this.form.requestSubmit()"
                   class="al-input {{ $filters->valueFrom !== '' ? 'al-input--active' : '' }}">
        </div>
        <div class="lg:col-span-2">
            <label class="block text-[11px] font-medium text-muted-foreground mb-1">To value</label>
            <input type="text" name="value_to" value="{{ $filters->valueTo }}" placeholder="e.g. cancelled"
                   onchange="this.form && this.form.requestSubmit()"
                   class="al-input {{ $filters->valueTo !== '' ? 'al-input--active' : '' }}">
        </div>
        <p class="sm:col-span-2 lg:col-span-6 text-[10px] text-muted-foreground/70 -mt-1">
            Find a specific transition — set a field to see every change to it, plus an optional old/new value to pin the exact step (e.g. status pending → cancelled).
        </p>
    </div>

    <noscript>
        <button type="submit" class="mt-3 inline-flex items-center gap-1.5 rounded-md bg-primary text-primary-foreground px-4 h-9 text-sm font-semibold">
            Apply
        </button>
    </noscript>
</form>
