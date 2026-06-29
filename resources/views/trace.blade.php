@extends('audit-log::layouts.app')

@section('title', 'Change chain — Yammi')

@section('content')
    <div class="mb-6">
        <a href="{{ route('audit-log.dashboard') }}" class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground mb-3">
            <i data-lucide="arrow-left" class="text-[13px]"></i> Back to log
        </a>
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
                <i data-lucide="git-fork" class="text-brand text-[20px]"></i> Change chain
            </h1>
            <button type="button" onclick="__alTraceToggleAll(this)" data-expanded="0"
                    class="inline-flex items-center gap-1.5 rounded-md border border-border bg-card px-3 h-8 text-xs font-semibold text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
                <i data-lucide="unfold-vertical" class="text-[14px]"></i> <span data-al-toggle-label>Expand all</span>
            </button>
        </div>
        <p class="text-sm text-muted-foreground mt-1">
            <span class="font-medium text-foreground">{{ $chain->count() }}</span> changes across
            <span class="font-medium text-foreground">{{ $chain->modelCount() }}</span> {{ \Illuminate\Support\Str::plural('model', $chain->modelCount()) }},
            started by <span class="font-medium text-foreground">{{ $chain->rootActorLabel() }}</span>
            on <span class="font-medium text-foreground">{{ $chain->rootModel() }}</span>.
        </p>
        <p class="mt-1 text-[11px] font-mono text-muted-foreground/70 break-all">{{ $chain->correlationId() }}</p>
    </div>

    <div class="space-y-2">
        @foreach ($chain->tree as $node)
            @include('audit-log::partials.trace-node', ['node' => $node, 'focus' => $focus])
        @endforeach
    </div>

    <p class="mt-3 text-[11px] text-muted-foreground">Each box is one unit of work; nesting shows which change caused which. Click an entry to see its field-level changes.</p>

    @push('scripts')<script>
        __alIcons();
        (function () {
            var focus = document.getElementById('al-focus-entry');
            if (focus) { focus.scrollIntoView({ block: 'center' }); }
        })();
        function __alTraceToggleAll(button) {
            var expand = button.getAttribute('data-expanded') !== '1';
            document.querySelectorAll('[id^="al-trace-diff-"]').forEach(function (diff) {
                diff.classList.toggle('hidden', !expand);
            });
            button.setAttribute('data-expanded', expand ? '1' : '0');
            button.querySelector('[data-al-toggle-label]').textContent = expand ? 'Collapse all' : 'Expand all';
        }
    </script>@endpush
@endsection
