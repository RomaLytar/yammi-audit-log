@php $focus = $focus ?? null; @endphp
<div @if ($node->depth() > 0) class="ms-4 border-s-2 border-border/60 ps-3" @endif>
    <div class="mb-1.5 mt-1 flex items-center gap-2 flex-wrap text-xs">
        <span class="inline-flex items-center gap-1.5 rounded-md bg-accent px-2 py-0.5 text-[11px] font-semibold text-foreground">
            <i data-lucide="{{ $node->processIcon() }}" class="text-[12px] text-brand"></i> {{ $node->processLabel() }}
        </span>
        @include('audit-log::partials.actor-badge', ['type' => $node->actorType(), 'label' => $node->actorLabel()])
        @if ($node->originLabel())
            <span class="inline-flex items-center gap-1 text-muted-foreground">
                <i data-lucide="corner-down-right" class="text-[12px] text-brand"></i> from {{ $node->originLabel() }}
            </span>
        @endif
        @if ($node->isRoot())
            <span class="inline-flex items-center gap-1 rounded-md bg-brand/10 px-2 py-0.5 text-[11px] font-medium text-brand ring-1 ring-inset ring-brand/30">
                <i data-lucide="flag" class="text-[11px]"></i> Root
            </span>
        @endif
        <span class="text-[11px] text-muted-foreground">{{ $node->entryCount() }} {{ \Illuminate\Support\Str::plural('change', $node->entryCount()) }}</span>
    </div>

    <ol class="relative border-s border-border ms-2">
        @foreach ($node->entries as $entry)
            @include('audit-log::partials.trace-entry', ['entry' => $entry, 'focus' => $focus])
        @endforeach
    </ol>

    @if ($node->hasChildren())
        <div class="mt-1 space-y-1">
            @foreach ($node->children as $child)
                @include('audit-log::partials.trace-node', ['node' => $child, 'focus' => $focus])
            @endforeach
        </div>
    @endif
</div>
