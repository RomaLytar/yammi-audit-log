@php
    $label = $label ?? null;
    $value = $value ?? '';
    $min = $min ?? '';
    $max = $max ?? '';
@endphp
<div>
    @if ($label)
        <label class="block text-[11px] font-medium text-muted-foreground mb-1">{{ $label }}</label>
    @endif
    <div class="relative al-datefield">
        <input type="date" name="{{ $name }}" value="{{ $value }}"
               @if ($min !== '') min="{{ $min }}" @endif
               @if ($max !== '') max="{{ $max }}" @endif
               onchange="this.form && this.form.requestSubmit()"
               onclick="try { this.showPicker(); } catch (e) {}"
               class="al-input pr-9 {{ $value !== '' ? 'al-input--active' : '' }}">
        <button type="button" tabindex="-1" aria-label="Open calendar"
                onclick="var i = this.closest('.al-datefield').querySelector('input'); try { i.showPicker(); } catch (e) { i.focus(); }"
                class="absolute right-0 top-0 h-full px-2.5 flex items-center text-muted-foreground hover:text-foreground">
            <i data-lucide="calendar" class="text-[14px]"></i>
        </button>
    </div>
</div>
