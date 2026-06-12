@php
    $icons = [
        'user' => 'user',
        'job' => 'cpu',
        'command' => 'terminal',
        'scheduler' => 'clock',
        'system' => 'server',
        'unknown' => 'help-circle',
    ];
    $icon = $icons[$type] ?? 'help-circle';
    $anonymous = in_array($type, ['system', 'unknown'], true);
    $feedUrl = $anonymous
        ? route('audit-log.dashboard', ['actor_type' => $type])
        : route('audit-log.dashboard', ['actor_type' => $type, 'actor' => $label]);
@endphp
<a href="{{ $feedUrl }}" title="All changes by {{ $label }}"
   class="inline-flex items-center gap-1.5 min-w-0 group/actor" onclick="event.stopPropagation()">
    <span class="inline-flex h-5 w-5 items-center justify-center rounded {{ $anonymous ? 'bg-muted text-muted-foreground' : 'bg-brand/10 text-brand' }} shrink-0">
        <i data-lucide="{{ $icon }}" class="text-[12px]"></i>
    </span>
    <span class="flex flex-col leading-tight min-w-0">
        <span class="truncate text-xs font-medium group-hover/actor:text-brand group-hover/actor:underline">{{ $label }}</span>
        <span class="text-[10px] uppercase tracking-wide text-muted-foreground">{{ $type }}</span>
    </span>
</a>
