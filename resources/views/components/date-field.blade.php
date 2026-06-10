@php
    $label = $label ?? null;
    $value = $value ?? '';
@endphp
<div>
    @if ($label)
        <label class="block text-[11px] font-medium text-muted-foreground mb-1">{{ $label }}</label>
    @endif
    <div class="relative al-datefield">
        <input type="date" name="{{ $name }}" value="{{ $value }}"
               onchange="this.form && this.form.requestSubmit()"
               class="al-input pr-9 {{ $value !== '' ? 'al-input--active' : '' }}">
        <i data-lucide="calendar" class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-[14px] text-muted-foreground"></i>
    </div>
</div>
