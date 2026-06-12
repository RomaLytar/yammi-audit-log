@extends('audit-log::layouts.app')

@section('title', 'Database — Yammi')

@section('content')
    <div class="mb-6">
        <a href="{{ route('audit-log.settings') }}" class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground mb-3">
            <i data-lucide="arrow-left" class="text-[13px]"></i> Settings
        </a>
        <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
            <i data-lucide="database" class="text-brand text-[20px]"></i> Database connection
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
            Where audit records are stored. To use a dedicated database, set <code class="text-[12px] bg-accent px-1 py-0.5 rounded">AUDIT_LOG_DB_CONNECTION</code> in .env (the full guide lives in config/audit-log.php), then transfer the data below.
        </p>
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

    <div class="rounded-xl border border-border bg-card p-5 shadow-xs">
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
            <div class="w-full sm:w-56">
                @include('audit-log::components.select', [
                    'name' => 'from',
                    'label' => 'From',
                    'options' => $connectionOptions,
                    'value' => $vm->defaultConnection->name,
                    'placeholder' => 'Source connection',
                    'autoSubmit' => false,
                ])
            </div>
            <div class="w-full sm:w-56">
                @include('audit-log::components.select', [
                    'name' => 'to',
                    'label' => 'To',
                    'options' => $connectionOptions,
                    'value' => $vm->suggestedTransferTarget(),
                    'placeholder' => 'Target connection',
                    'autoSubmit' => false,
                ])
            </div>
            <label class="inline-flex items-center gap-2 h-9 cursor-pointer select-none">
                <input type="checkbox" name="delete_source" value="1" class="h-4 w-4 rounded border-border accent-current">
                <span class="text-xs text-muted-foreground">drop source tables after transfer</span>
            </label>
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-primary text-primary-foreground px-3 h-9 text-xs font-semibold hover:opacity-90"
                    onclick="return confirm('Transfer audit data between connections now?');">
                <i data-lucide="arrow-right-left" class="text-[13px]"></i> Transfer data
            </button>
        </form>
        <p class="text-[11px] text-muted-foreground mt-3">Runs synchronously — for very large tables prefer <code class="bg-accent px-1 py-0.5 rounded">php artisan audit-log:transfer-data</code>.</p>
    </div>

    @push('scripts')<script>__alIcons();</script>@endpush
@endsection
