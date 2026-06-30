@extends('audit-log::layouts.app')

@section('title', 'Change chain — Yammi')

@section('content')
    <style>
        .al-canvas-shell { position: relative; margin-top: 6px; }
        .al-canvas {
            border: 1px solid hsl(var(--border)); border-radius: 16px;
            background-color: hsl(var(--muted) / 0.22);
            background-image: radial-gradient(hsl(var(--muted-foreground) / 0.16) 1px, transparent 1.5px);
            background-size: 22px 22px;
            overflow: auto; max-height: 74vh; cursor: grab;
        }
        .al-canvas.al-grabbing { cursor: grabbing; user-select: none; }
        .al-canvas__hint {
            position: absolute; top: 10px; right: 12px; z-index: 5; pointer-events: none;
            display: inline-flex; align-items: center; gap: 5px;
            background: hsl(var(--card) / 0.88); border: 1px solid hsl(var(--border));
            border-radius: 8px; padding: 3px 9px; font-size: 11px; font-weight: 500; color: hsl(var(--muted-foreground));
            box-shadow: 0 1px 3px rgb(0 0 0 / 0.06);
        }
        .al-canvas__hint [data-lucide] { width: 13px; height: 13px; }
        .al-tree { display: inline-block; min-width: 100%; padding: 30px 22px 34px; }
        .al-tree ul { display: flex; justify-content: center; padding-top: 26px; position: relative; list-style: none; margin: 0; }
        .al-tree li { position: relative; padding: 26px 14px 0; display: flex; flex-direction: column; align-items: center; }
        .al-tree li::before, .al-tree li::after {
            content: ''; position: absolute; top: 0; right: 50%;
            border-top: 2px solid hsl(var(--border)); width: 50%; height: 26px;
        }
        .al-tree li::after { right: auto; left: 50%; border-left: 2px solid hsl(var(--border)); }
        .al-tree li:only-child::before, .al-tree li:only-child::after { display: none; }
        .al-tree li:only-child { padding-top: 26px; }
        .al-tree li:first-child::before, .al-tree li:last-child::after { border: 0 none; }
        .al-tree li:last-child::before { border-right: 2px solid hsl(var(--border)); border-top-right-radius: 8px; }
        .al-tree li:first-child::after { border-top-left-radius: 8px; }
        .al-tree ul ul::before {
            content: ''; position: absolute; top: 0; left: 50%;
            border-left: 2px solid hsl(var(--border)); width: 0; height: 26px;
        }
        .al-tree > ul { padding-top: 0; }
        .al-tree > ul > li { padding-top: 0; }
        .al-tree > ul > li::before, .al-tree > ul > li::after { display: none; }

        .al-node {
            position: relative;
            width: clamp(300px, calc((min(100vw, 80rem) - 8rem) / var(--al-cols, 3)), calc(min(100vw, 80rem) - 8rem));
            background: hsl(var(--card)); border: 1px solid hsl(var(--border));
            border-top: 3px solid hsl(var(--muted-foreground));
            border-radius: 12px;
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
        }
        .al-node--user { border-top-color: hsl(var(--brand)); }
        .al-node--job { border-top-color: hsl(var(--info)); }
        .al-node--command { border-top-color: hsl(var(--warning)); }
        .al-node--scheduler { border-top-color: hsl(var(--success)); }
        .al-node--root { box-shadow: 0 0 0 3px hsl(var(--brand) / 0.14), 0 1px 2px rgb(0 0 0 / 0.05); }

        .al-node__head { width: 100%; display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; background: transparent; border: 0; cursor: pointer; padding: 9px 11px; text-align: left; font: inherit; color: inherit; border-radius: 11px; }
        .al-node__head:hover { background: hsl(var(--accent) / 0.5); }
        .al-node__head-main { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
        .al-node__head-side { display: flex; align-items: center; gap: 7px; flex: 0 0 auto; padding-top: 1px; }
        .al-node__proc { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; letter-spacing: .01em; color: hsl(var(--brand)); text-transform: uppercase; }
        .al-node__proc [data-lucide] { width: 13px; height: 13px; }
        .al-node__flag { display: inline-flex; align-items: center; gap: 3px; font-size: 10px; font-weight: 700; color: hsl(var(--brand)); background: hsl(var(--brand) / 0.1); border: 1px solid hsl(var(--brand) / 0.3); border-radius: 6px; padding: 1px 6px; }
        .al-node__flag [data-lucide] { width: 10px; height: 10px; }
        .al-node__count { font-size: 11px; color: hsl(var(--muted-foreground)); white-space: nowrap; }
        .al-node__chev { width: 15px; height: 15px; color: hsl(var(--muted-foreground)); transition: transform .15s ease; }
        .al-node--open .al-node__chev { transform: rotate(180deg); }
        .al-node__actor { font-size: 13px; font-weight: 600; color: hsl(var(--foreground)); line-height: 1.25; word-break: break-word; }
        .al-node__from { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: hsl(var(--muted-foreground)); }
        .al-node__from [data-lucide] { width: 12px; height: 12px; color: hsl(var(--brand)); }

        .al-node__body { display: none; flex-direction: column; gap: 2px; padding: 1px 9px 8px; border-top: 1px solid hsl(var(--border)); margin: 0 2px; }
        .al-node--open .al-node__body { display: flex; }
        .al-node__entry { display: flex; align-items: center; gap: 6px; width: 100%; text-align: left; background: transparent; border: 0; padding: 5px 6px; border-radius: 8px; cursor: pointer; font-size: 12px; color: hsl(var(--foreground)); margin-top: 4px; }
        .al-node__entry:hover { background: hsl(var(--accent)); }
        .al-node__entry--focus { background: hsl(var(--brand) / 0.1); box-shadow: inset 0 0 0 1px hsl(var(--brand) / 0.4); }
        .al-node__dot { width: 8px; height: 8px; border-radius: 99px; flex: 0 0 auto; background: hsl(var(--muted-foreground)); }
        .al-node__dot--created { background: hsl(var(--success)); }
        .al-node__dot--updated { background: hsl(var(--info)); }
        .al-node__dot--deleted { background: hsl(var(--destructive)); }
        .al-node__dot--restored { background: hsl(var(--warning)); }
        .al-node__entry-model { font-weight: 600; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .al-node__entry-id { color: hsl(var(--muted-foreground)); font-family: ui-monospace, monospace; font-size: 11px; }
        .al-node__entry-fields { margin-left: auto; font-size: 11px; color: hsl(var(--muted-foreground)); white-space: nowrap; }
        .al-node__here { display: inline-flex; align-items: center; gap: 3px; font-size: 10px; font-weight: 700; color: hsl(var(--brand-foreground)); background: hsl(var(--brand)); border-radius: 6px; padding: 1px 6px; }
        .al-node__here [data-lucide] { width: 11px; height: 11px; }

        .al-node__diff { margin: 1px 2px 4px; border: 1px solid hsl(var(--border)); border-radius: 8px; overflow-x: auto; }
        .al-node__diff table { width: 100%; border-collapse: collapse; font-family: ui-monospace, monospace; font-size: 11px; }
        .al-node__diff th { text-align: left; padding: 4px 8px; font-size: 9px; letter-spacing: .04em; text-transform: uppercase; color: hsl(var(--muted-foreground)); background: hsl(var(--muted) / 0.5); }
        .al-node__diff td { padding: 4px 8px; border-top: 1px solid hsl(var(--border)); vertical-align: top; overflow-wrap: anywhere; word-break: normal; }
        .al-node__diff .al-old { color: hsl(var(--destructive)); }
        .al-node__diff .al-new { color: hsl(var(--success)); }

        .al-trace-link { display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; font-size: 11px; font-weight: 500; color: hsl(var(--primary)); text-decoration: none; }
        .al-trace-link:hover { text-decoration: underline; }
        .al-trace-link [data-lucide] { width: 11px; height: 11px; }
    </style>

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
        @if ($chain->traceUrl())
            <a href="{{ $chain->traceUrl() }}" target="_blank" rel="noopener noreferrer" class="al-trace-link">
                <i data-lucide="external-link"></i>
                Open distributed trace
            </a>
        @elseif ($chain->traceId())
            <p class="mt-0.5 text-[11px] font-mono text-muted-foreground/70 break-all">trace {{ $chain->traceId() }}</p>
        @endif
    </div>

    <div class="al-canvas-shell">
        <span class="al-canvas__hint"><i data-lucide="move"></i> Drag to pan</span>
        <div class="al-canvas">
            <div class="al-tree" style="--al-cols: {{ $chain->columns() }}">
                <ul>
                    @foreach ($chain->tree as $node)
                        @include('audit-log::partials.trace-node', ['node' => $node, 'focus' => $focus])
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <p class="mt-2 text-[11px] text-muted-foreground">Each box is one unit of work (a request, job or command); a line runs from the change that caused the next one. Click a box to see its field-level changes, or drag the canvas to pan a wide tree.</p>

    @push('scripts')<script>
        __alIcons();
        (function () {
            var canvas = document.querySelector('.al-canvas');
            if (canvas) {
                var down = false, startX, startY, sLeft, sTop;
                canvas.addEventListener('mousedown', function (e) {
                    if (e.button !== 0 || e.target.closest('button, a')) { return; }
                    down = true; startX = e.clientX; startY = e.clientY;
                    sLeft = canvas.scrollLeft; sTop = canvas.scrollTop;
                    canvas.classList.add('al-grabbing');
                });
                window.addEventListener('mouseup', function () { down = false; canvas.classList.remove('al-grabbing'); });
                window.addEventListener('mousemove', function (e) {
                    if (!down) { return; }
                    canvas.scrollLeft = sLeft - (e.clientX - startX);
                    canvas.scrollTop = sTop - (e.clientY - startY);
                });
                canvas.scrollLeft = Math.max(0, (canvas.scrollWidth - canvas.clientWidth) / 2);
            }
            var focus = document.getElementById('al-focus-entry');
            if (focus) { focus.scrollIntoView({ block: 'center' }); }
        })();
        function __alToggleCard(btn) {
            var card = btn.closest('.al-node');
            if (!card) { return; }
            var open = card.classList.toggle('al-node--open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        function __alTraceToggleAll(button) {
            var expand = button.getAttribute('data-expanded') !== '1';
            document.querySelectorAll('.al-node').forEach(function (card) {
                card.classList.toggle('al-node--open', expand);
            });
            document.querySelectorAll('.al-node__diff').forEach(function (diff) {
                diff.classList.toggle('hidden', !expand);
            });
            button.setAttribute('data-expanded', expand ? '1' : '0');
            button.querySelector('[data-al-toggle-label]').textContent = expand ? 'Collapse all' : 'Expand all';
        }
    </script>@endpush
@endsection
