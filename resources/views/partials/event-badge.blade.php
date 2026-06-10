@php
    $map = [
        'created' => ['tone' => 'text-success ring-success/30 bg-success/10', 'icon' => 'plus-circle'],
        'updated' => ['tone' => 'text-info ring-info/30 bg-info/10', 'icon' => 'pencil'],
        'deleted' => ['tone' => 'text-destructive ring-destructive/30 bg-destructive/10', 'icon' => 'trash-2'],
        'restored' => ['tone' => 'text-warning ring-warning/30 bg-warning/10', 'icon' => 'rotate-ccw'],
    ];
    $cfg = $map[$event] ?? ['tone' => 'text-muted-foreground ring-border bg-muted/40', 'icon' => 'circle'];
@endphp
<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium ring-1 ring-inset {{ $cfg['tone'] }}">
    <i data-lucide="{{ $cfg['icon'] }}" class="text-[12px]"></i>
    {{ ucfirst($event) }}
</span>
