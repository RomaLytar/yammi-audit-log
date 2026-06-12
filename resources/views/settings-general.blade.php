@extends('audit-log::layouts.app')

@section('title', 'General settings — Yammi')

@section('content')
    <div class="mb-6">
        <a href="{{ route('audit-log.settings') }}" class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground mb-3">
            <i data-lucide="arrow-left" class="text-[13px]"></i> Settings
        </a>
        <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
            <i data-lucide="sliders-horizontal" class="text-brand text-[20px]"></i> General settings
        </h1>
        <p class="text-sm text-muted-foreground mt-1">Saved values are stored in the audit database and override the published config; package defaults apply when neither is set.</p>
    </div>

    @if (session('audit_log_status'))
        <div class="mb-4 rounded-lg border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
            {{ session('audit_log_status') }}
        </div>
    @endif

    @if (isset($errors) && $errors->any())
        <div class="mb-4 rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('audit-log.settings.update') }}">
        @csrf

        @foreach ($vm->sections() as $section)
            <div class="rounded-xl border border-border bg-card p-5 shadow-xs mb-6">
                <h2 class="text-sm font-semibold flex items-center gap-2 mb-4">
                    <i data-lucide="{{ $section['icon'] }}" class="text-brand text-[15px]"></i> {{ $section['title'] }}
                </h2>

                <div class="space-y-5">
                    @foreach ($section['settings'] as $setting)
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
                            <div class="max-w-xl">
                                <label for="setting-{{ $setting->definition->key }}" class="text-xs font-semibold">{{ $setting->definition->label }}</label>
                                <p class="text-xs text-muted-foreground mt-0.5">{{ $setting->definition->description }}</p>
                            </div>

                            @if ($setting->definition->options !== null)
                                @php
                                    $current = $setting->inputValue();
                                    $presetOptions = $setting->definition->options;
                                    if (! array_key_exists($current, $presetOptions) && $current !== '') {
                                        $presetOptions[$current] = 'Custom: '.$current;
                                    }
                                    $presetOptions['__custom'] = 'Custom…';
                                @endphp
                                <div class="w-full sm:w-72 sm:shrink-0 space-y-2" data-al-custom-select>
                                    @include('audit-log::components.select', [
                                        'name' => $setting->definition->key,
                                        'options' => $presetOptions,
                                        'value' => $current,
                                        'placeholder' => 'Select…',
                                        'autoSubmit' => false,
                                    ])
                                    <input type="text" name="{{ $setting->definition->key }}" value="{{ $current }}" disabled
                                           placeholder="{{ $setting->definition->type->value === 'integer' ? 'e.g. 45' : 'e.g. 30 2 * * *' }}"
                                           class="hidden w-full h-9 rounded-md border border-input bg-card px-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-ring"
                                           data-al-custom-input>
                                </div>
                            @elseif ($setting->definition->type->value === 'boolean')
                                <input type="hidden" name="{{ $setting->definition->key }}" value="0">
                                <label class="inline-flex items-center gap-2 shrink-0 cursor-pointer select-none">
                                    <input type="checkbox" name="{{ $setting->definition->key }}" id="setting-{{ $setting->definition->key }}" value="1"
                                           {{ (bool) $setting->value ? 'checked' : '' }}
                                           class="h-4 w-4 rounded border-border accent-current">
                                    <span class="text-xs text-muted-foreground">enabled</span>
                                </label>
                            @elseif ($setting->definition->type->value === 'integer')
                                <div class="flex items-center gap-2 shrink-0">
                                    <input type="number" name="{{ $setting->definition->key }}" id="setting-{{ $setting->definition->key }}"
                                           value="{{ old($setting->definition->key, $setting->inputValue()) }}"
                                           @if ($setting->definition->min !== null) min="{{ $setting->definition->min }}" @endif
                                           @if ($setting->definition->max !== null) max="{{ $setting->definition->max }}" @endif
                                           class="w-40 h-9 rounded-md border border-input bg-card px-3 text-sm tabular-nums focus:outline-none focus:ring-2 focus:ring-ring">
                                    @if ($setting->definition->suffix !== null)
                                        <span class="text-xs text-muted-foreground">{{ $setting->definition->suffix }}</span>
                                    @endif
                                </div>
                            @else
                                <input type="text" name="{{ $setting->definition->key }}" id="setting-{{ $setting->definition->key }}"
                                       value="{{ old($setting->definition->key, $setting->inputValue()) }}"
                                       class="w-full sm:w-72 h-9 rounded-md border border-input bg-card px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring sm:shrink-0">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="flex items-center gap-2 mb-6 -mt-2">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-primary text-primary-foreground px-3 h-9 text-xs font-semibold hover:opacity-90">
                <i data-lucide="save" class="text-[13px]"></i> Save settings
            </button>
            <button type="submit" form="audit-settings-reset" class="inline-flex items-center gap-1.5 rounded-md border border-border bg-card px-3 h-9 text-xs font-medium text-muted-foreground hover:bg-accent">
                <i data-lucide="rotate-ccw" class="text-[13px]"></i> Reset to defaults
            </button>
        </div>
    </form>
    <form id="audit-settings-reset" method="POST" action="{{ route('audit-log.settings.reset') }}">@csrf</form>

    @push('scripts')
    <script>
        __alIcons();

        document.querySelectorAll('[data-al-custom-select]').forEach(function (wrapper) {
            var hidden = wrapper.querySelector('[data-al-select-input]');
            var custom = wrapper.querySelector('[data-al-custom-input]');

            wrapper.addEventListener('click', function () {
                setTimeout(function () {
                    if (hidden.value === '__custom') {
                        hidden.disabled = true;
                        custom.disabled = false;
                        custom.classList.remove('hidden');
                        custom.focus();
                    } else if (hidden.value !== '') {
                        hidden.disabled = false;
                        custom.disabled = true;
                        custom.classList.add('hidden');
                    }
                }, 0);
            });
        });
    </script>
    @endpush
@endsection
