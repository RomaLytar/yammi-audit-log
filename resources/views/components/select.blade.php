@php
    $label = $label ?? null;
    $placeholder = $placeholder ?? 'All';
    $value = (string) ($value ?? '');
    $options = $options ?? [];
    $autoSubmit = $autoSubmit ?? true;
    $triggerLabel = $options[$value] ?? $placeholder;
    $isActive = $value !== '';
    $id = 'al-sel-'.bin2hex(random_bytes(4));
@endphp
<div>
    @if ($label)
        <label class="block text-[11px] font-medium text-muted-foreground mb-1">{{ $label }}</label>
    @endif
    <div class="relative" data-al-select data-al-select-id="{{ $id }}" {{ $autoSubmit ? '' : 'data-al-select-nosubmit' }}>
        <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-al-select-input>
        <button type="button"
                class="w-full inline-flex items-center justify-between gap-2 h-9 rounded-md border bg-card text-sm px-3 transition-colors focus:outline-none focus:ring-2 focus:ring-ring
                       {{ $isActive ? 'border-brand/40 bg-brand/5 text-foreground font-medium' : 'border-input text-foreground hover:bg-accent/40' }}"
                data-al-select-trigger aria-haspopup="listbox" aria-expanded="false">
            <span class="truncate {{ $isActive ? '' : 'text-muted-foreground' }}" data-al-select-label>{{ $triggerLabel }}</span>
            <i data-lucide="chevron-down" class="text-[14px] text-muted-foreground shrink-0 transition-transform" data-al-select-caret></i>
        </button>
        <div class="hidden absolute z-30 mt-1 left-0 w-full rounded-md border border-border bg-popover text-popover-foreground shadow-lg animate-slide-down overflow-hidden"
             data-al-select-dropdown role="listbox">
            <ul class="p-1 max-h-60 overflow-y-auto overscroll-contain">
                @foreach ($options as $optValue => $optLabel)
                    @php $selected = $value === (string) $optValue; @endphp
                    <li role="option" data-al-select-option data-value="{{ $optValue }}"
                        class="flex items-center gap-2 px-2 py-1.5 text-sm rounded-sm cursor-pointer {{ $selected ? 'bg-brand/10 text-foreground font-medium' : 'text-foreground hover:bg-accent hover:text-accent-foreground' }}">
                        <span class="w-4 inline-flex justify-center">
                            @if ($selected)<i data-lucide="check" class="text-[13px] text-brand"></i>@endif
                        </span>
                        <span class="truncate" data-al-select-option-label>{{ $optLabel }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
