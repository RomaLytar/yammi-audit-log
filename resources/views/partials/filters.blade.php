@php
    $active = trim(implode('', $filters)) !== '';
@endphp
<form method="GET" action="{{ route('audit-log.dashboard') }}" class="mb-5 rounded-xl border border-border bg-card p-4 shadow-xs">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <div>
            <label class="block text-[11px] font-medium text-muted-foreground mb-1">Model</label>
            <select name="type" class="al-input">
                <option value="">All models</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ class_basename($type) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-medium text-muted-foreground mb-1">Event</label>
            <select name="event" class="al-input">
                <option value="">All events</option>
                @foreach ($events as $event)
                    <option value="{{ $event }}" @selected($filters['event'] === $event)>{{ ucfirst($event) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-medium text-muted-foreground mb-1">Actor type</label>
            <select name="actor_type" class="al-input">
                <option value="">All actors</option>
                @foreach ($actorTypes as $actorType)
                    <option value="{{ $actorType }}" @selected($filters['actor_type'] === $actorType)>{{ ucfirst($actorType) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-medium text-muted-foreground mb-1">Actor name</label>
            <input type="text" name="actor" value="{{ $filters['actor'] }}" placeholder="e.g. John Doe" class="al-input">
        </div>
        <div>
            <label class="block text-[11px] font-medium text-muted-foreground mb-1">From</label>
            <input type="date" name="from" value="{{ $filters['from'] }}" class="al-input">
        </div>
        <div>
            <label class="block text-[11px] font-medium text-muted-foreground mb-1">To</label>
            <input type="date" name="to" value="{{ $filters['to'] }}" class="al-input">
        </div>
    </div>
    <div class="mt-3 flex items-center gap-2">
        <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-primary text-primary-foreground px-4 h-9 text-sm font-semibold hover:bg-primary/90">
            <i data-lucide="filter" class="text-[14px]"></i> Apply
        </button>
        @if ($active)
            <a href="{{ route('audit-log.dashboard') }}" class="inline-flex items-center gap-1.5 rounded-md border border-border bg-card px-4 h-9 text-sm font-medium hover:bg-accent">
                <i data-lucide="x" class="text-[14px]"></i> Clear
            </a>
        @endif
    </div>
</form>
