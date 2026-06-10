@php
    $active = trim(implode('', $filters)) !== '';

    $typeOptions = ['' => 'All models'];
    foreach ($types as $type) {
        $typeOptions[$type] = class_basename($type);
    }

    $eventOptions = ['' => 'All events'];
    foreach ($events as $event) {
        $eventOptions[$event] = ucfirst($event);
    }

    $actorOptions = ['' => 'All actors'];
    foreach ($actorTypes as $actorType) {
        $actorOptions[$actorType] = ucfirst($actorType);
    }
@endphp
<form method="GET" action="{{ route('audit-log.dashboard') }}" class="mb-5 rounded-xl border border-border bg-card p-4 shadow-xs">
    <div class="flex items-center justify-between mb-3">
        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
            <i data-lucide="sliders-horizontal" class="text-[14px]"></i> Filters
            <span class="text-[10px] text-muted-foreground/70">(applied automatically)</span>
        </span>
        @if ($active)
            <a href="{{ route('audit-log.dashboard') }}" class="inline-flex items-center gap-1.5 rounded-md border border-border bg-card px-3 h-8 text-xs font-medium hover:bg-accent">
                <i data-lucide="x" class="text-[13px]"></i> Clear
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        @include('audit-log::components.select', ['name' => 'type', 'label' => 'Model', 'options' => $typeOptions, 'value' => $filters['type'], 'placeholder' => 'All models'])
        @include('audit-log::components.select', ['name' => 'event', 'label' => 'Event', 'options' => $eventOptions, 'value' => $filters['event'], 'placeholder' => 'All events'])
        @include('audit-log::components.select', ['name' => 'actor_type', 'label' => 'Actor type', 'options' => $actorOptions, 'value' => $filters['actor_type'], 'placeholder' => 'All actors'])
        <div>
            <label class="block text-[11px] font-medium text-muted-foreground mb-1">Actor name</label>
            <input type="text" name="actor" value="{{ $filters['actor'] }}" placeholder="e.g. John Doe"
                   onchange="this.form && this.form.requestSubmit()"
                   class="al-input {{ $filters['actor'] !== '' ? 'al-input--active' : '' }}">
        </div>
        @include('audit-log::components.date-field', ['name' => 'from', 'label' => 'From', 'value' => $filters['from']])
        @include('audit-log::components.date-field', ['name' => 'to', 'label' => 'To', 'value' => $filters['to']])
    </div>

    <noscript>
        <button type="submit" class="mt-3 inline-flex items-center gap-1.5 rounded-md bg-primary text-primary-foreground px-4 h-9 text-sm font-semibold">
            Apply
        </button>
    </noscript>
</form>
