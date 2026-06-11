@extends('audit-log::layouts.app')

@section('title', 'Settings — Yammi')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
            <i data-lucide="settings" class="text-brand text-[20px]"></i> Settings
        </h1>
        <p class="text-sm text-muted-foreground mt-1">Configure the audit log. Database values override config; bootstrap-critical values stay in config only.</p>
    </div>

    @if (session('audit_log_status'))
        <div class="mb-4 rounded-lg border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
            {{ session('audit_log_status') }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($vm->blocks() as $block)
            <div class="group relative overflow-hidden rounded-xl border border-border bg-card p-5 flex flex-col gap-3 shadow-xs hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <h2 class="text-base font-semibold tracking-tight">{{ $block['name'] }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground">{{ $block['description'] }}</p>
                    </div>
                    @if ($block['enabled'])
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium ring-1 ring-inset bg-success/10 text-success ring-success/25">
                            <i data-lucide="check-circle-2" class="text-[12px]"></i> Enabled
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium ring-1 ring-inset bg-muted text-muted-foreground ring-border">
                            <i data-lucide="power-off" class="text-[12px]"></i> Disabled
                        </span>
                    @endif
                </div>
                <div class="pt-3 border-t border-border">
                    <a href="{{ route($block['route']) }}"
                       class="inline-flex items-center gap-1 text-sm font-medium text-brand hover:text-brand/80 transition-colors">
                        Configure
                        <i data-lucide="arrow-right" class="text-[13px]"></i>
                    </a>
                </div>
                <div aria-hidden="true" class="pointer-events-none absolute -bottom-12 -right-12 h-32 w-32 rounded-full bg-gradient-to-tr from-transparent to-brand/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </div>
        @endforeach
    </div>

    @push('scripts')<script>__alIcons();</script>@endpush
@endsection
