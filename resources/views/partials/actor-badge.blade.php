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
@endphp
<span class="inline-flex items-center gap-1.5 min-w-0">
    <span class="inline-flex h-5 w-5 items-center justify-center rounded {{ $anonymous ? 'bg-muted text-muted-foreground' : 'bg-brand/10 text-brand' }} shrink-0">
        <i data-lucide="{{ $icon }}" class="text-[12px]"></i>
    </span>
    <span class="flex flex-col leading-tight min-w-0">
        <span class="truncate text-xs font-medium">{{ $label }}</span>
        <span class="text-[10px] uppercase tracking-wide text-muted-foreground">{{ $type }}</span>
    </span>
</span>
