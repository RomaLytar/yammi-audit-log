@extends('audit-log::layouts.app')

@section('title', 'Settings — Yammi')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
            <i data-lucide="settings" class="text-brand text-[20px]"></i> Settings
        </h1>
        <p class="text-sm text-muted-foreground mt-1">Package behavior — data retention and the audit database.</p>
    </div>

    @if (session('audit_log_status'))
        <div class="mb-4 rounded-lg border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
            {{ session('audit_log_status') }}
        </div>
    @endif

    @if (session('audit_log_error'))
        <div class="mb-4 rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
            {{ session('audit_log_error') }}
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
                <div class="mb-4">
                    <h2 class="text-sm font-semibold flex items-center gap-2">
                        <i data-lucide="{{ $section['icon'] }}" class="text-brand text-[15px]"></i> {{ $section['title'] }}
                    </h2>
                    @if ($loop->first)
                        <p class="text-xs text-muted-foreground mt-1">Saved values are stored in the audit database and override the published config; package defaults apply when neither is set.</p>
                    @endif
                </div>

                <div class="space-y-5">
                    @foreach ($section['settings'] as $setting)
                        <div class="flex items-start justify-between gap-6">
                            <div class="max-w-xl">
                                <label for="setting-{{ $setting->definition->key }}" class="text-xs font-semibold">{{ $setting->definition->label }}</label>
                                <p class="text-xs text-muted-foreground mt-0.5">{{ $setting->definition->description }}</p>
                            </div>

                            @if ($setting->definition->type->value === 'boolean')
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
                                       class="w-72 h-9 rounded-md border border-input bg-card px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring shrink-0">
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

    <div class="rounded-xl border border-border bg-card p-5 shadow-xs">
        <div class="mb-4">
            <h2 class="text-sm font-semibold flex items-center gap-2">
                <i data-lucide="database" class="text-brand text-[15px]"></i> Audit database
            </h2>
            <p class="text-xs text-muted-foreground mt-1">
                Where audit records are stored. To use a dedicated database, set <code class="text-[11px] bg-accent px-1 py-0.5 rounded">AUDIT_LOG_DB_CONNECTION</code> in .env (see config/audit-log.php for the full guide), then transfer the data below.
            </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 mb-5">
            @foreach ([['Application default', $vm->defaultConnection, !$vm->hasDedicatedConnection()], ['Dedicated audit DB', $vm->dedicatedConnection, $vm->hasDedicatedConnection()]] as [$title, $status, $active])
                <div class="rounded-lg border {{ $active ? 'border-brand/40 bg-brand/5' : 'border-border' }} p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold">{{ $title }}</span>
                        @if ($active)
                            <span class="inline-flex items-center rounded-full bg-brand/15 text-brand text-[10px] font-bold px-2 py-0.5">in use</span>
                        @endif
                    </div>
                    @if ($status !== null)
                        <dl class="text-xs space-y-1">
                            <div class="flex justify-between"><dt class="text-muted-foreground">Connection</dt><dd class="font-medium">{{ $status->name }}</dd></div>
                            <div class="flex justify-between"><dt class="text-muted-foreground">Driver / DB</dt><dd class="font-medium">{{ $status->driver }} / {{ $status->database }}</dd></div>
                            <div class="flex justify-between"><dt class="text-muted-foreground">Status</dt>
                                <dd class="font-medium {{ $status->reachable ? 'text-success' : 'text-destructive' }}">
                                    {{ $status->reachable ? ($status->migrated ? 'ready' : 'reachable, not migrated') : 'unreachable' }}
                                </dd>
                            </div>
                            <div class="flex justify-between"><dt class="text-muted-foreground">Audit rows</dt><dd class="font-medium tabular-nums">{{ $status->rowCount }}</dd></div>
                        </dl>
                    @else
                        <p class="text-xs text-muted-foreground">Not configured — records go to the application default database.</p>
                    @endif
                </div>
            @endforeach
        </div>

        @php
            $connectionOptions = [];
            foreach ($vm->connectionNames as $name) {
                $connectionOptions[$name] = $name;
            }
        @endphp
        <form method="POST" action="{{ route('audit-log.settings.transfer') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="w-56">
                @include('audit-log::components.select', [
                    'name' => 'from',
                    'label' => 'From',
                    'options' => $connectionOptions,
                    'value' => $vm->defaultConnection->name,
                    'placeholder' => 'Source connection',
                    'autoSubmit' => false,
                ])
            </div>
            <div class="w-56">
                @include('audit-log::components.select', [
                    'name' => 'to',
                    'label' => 'To',
                    'options' => $connectionOptions,
                    'value' => $vm->suggestedTransferTarget(),
                    'placeholder' => 'Target connection',
                    'autoSubmit' => false,
                ])
            </div>
            <label class="inline-flex items-center gap-2 h-8 cursor-pointer select-none">
                <input type="checkbox" name="delete_source" value="1" class="h-4 w-4 rounded border-border accent-current">
                <span class="text-xs text-muted-foreground">drop source tables after transfer</span>
            </label>
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-primary text-primary-foreground px-3 h-8 text-xs font-semibold hover:opacity-90"
                    onclick="return confirm('Transfer audit data between connections now?');">
                <i data-lucide="arrow-right-left" class="text-[13px]"></i> Transfer data
            </button>
        </form>
        <p class="text-[11px] text-muted-foreground mt-3">Runs synchronously — for very large tables prefer <code class="bg-accent px-1 py-0.5 rounded">php artisan audit-log:transfer-data</code>.</p>
    </div>

    @push('scripts')<script>__alIcons();</script>@endpush
@endsection
