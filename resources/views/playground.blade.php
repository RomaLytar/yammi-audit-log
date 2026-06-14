@extends('audit-log::layouts.app')

@section('title', 'Facades — Yammi')

@section('content')
    <div class="mb-6">
        <a href="{{ route('audit-log.settings') }}" class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground mb-3">
            <i data-lucide="arrow-left" class="text-[13px]"></i> Settings
        </a>
        <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
            <i data-lucide="terminal" class="text-brand text-[20px]"></i> Facade playground
        </h1>
        <p class="text-sm text-muted-foreground mt-1">
            Every public <code class="text-[12px] bg-accent px-1 py-0.5 rounded">AuditLog</code> facade method — pick one on the left to see what it does, a real example, and a form to run it right here.
        </p>
    </div>

    <div class="lg:grid lg:grid-cols-[230px_minmax(0,1fr)] lg:gap-8">
        <nav class="mb-6 lg:mb-0 lg:sticky lg:top-20 lg:self-start" aria-label="Facade methods">
            <div class="flex flex-wrap gap-1.5 lg:flex-col lg:gap-0.5">
                @foreach ($methods as $method)
                    <button type="button" data-al-method-link="{{ $method->key }}"
                            class="group inline-flex items-center justify-between gap-2 rounded-md px-2.5 py-1.5 text-xs font-medium text-left text-muted-foreground hover:text-foreground hover:bg-accent transition-colors {{ $loop->first ? 'bg-brand/10 text-brand' : '' }}">
                        <span class="font-mono truncate">{{ $method->key }}()</span>
                        <i data-lucide="{{ $method->destructive ? 'pen-line' : 'eye' }}" class="text-[12px] shrink-0 {{ $method->destructive ? 'text-warning' : 'text-success' }}"
                           title="{{ $method->destructive ? 'writes data' : 'read-only' }}"></i>
                    </button>
                @endforeach
            </div>
        </nav>

        <div class="min-w-0">
            @foreach ($methods as $method)
                <div data-al-method-panel="{{ $method->key }}" class="rounded-xl border border-border bg-card p-5 shadow-xs min-w-0 overflow-hidden {{ $loop->first ? '' : 'hidden' }}" data-al-method="{{ $method->key }}">
                    <div class="flex items-start justify-between gap-4 mb-1">
                        <h2 class="text-sm font-semibold font-mono">AuditLog::{{ $method->key }}()</h2>
                        @if ($method->destructive)
                            <span class="inline-flex items-center gap-1 rounded-md bg-warning/10 px-2 py-0.5 text-[10px] font-medium text-warning ring-1 ring-inset ring-warning/30">
                                <i data-lucide="pen-line" class="text-[10px]"></i> writes data
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-md bg-success/10 px-2 py-0.5 text-[10px] font-medium text-success ring-1 ring-inset ring-success/30">
                                <i data-lucide="eye" class="text-[10px]"></i> read-only
                            </span>
                        @endif
                    </div>

                    <p class="text-xs text-muted-foreground mb-3 max-w-3xl">{{ $method->summary }}</p>

                    <div class="rounded-lg border border-border bg-muted/30 px-3 py-2 mb-3 overflow-x-auto">
                        <code class="text-[11px] font-mono whitespace-nowrap">{{ $method->signature }}</code>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="min-w-0">
                            <div class="text-[11px] font-medium text-muted-foreground uppercase tracking-wider mb-1.5">Example</div>
                            <pre class="rounded-lg border border-border bg-muted/30 p-3 text-[11px] font-mono overflow-x-auto leading-relaxed"><code>{{ $method->example }}</code></pre>
                        </div>

                        <div class="min-w-0">
                            <div class="text-[11px] font-medium text-muted-foreground uppercase tracking-wider mb-1.5">Try it</div>
                            <form class="space-y-3" data-al-playground-form data-method="{{ $method->key }}">
                                @foreach ($method->arguments as $argument)
                                    <div>
                                        <label class="block text-[11px] font-medium text-muted-foreground mb-1">
                                            {{ $argument->name }}
                                            <span class="text-muted-foreground/60">({{ $argument->type }}{{ $argument->required ? ', required' : '' }})</span>
                                        </label>
                                        <input type="text" name="{{ $argument->name }}" placeholder="{{ $argument->placeholder }}"
                                               class="w-full h-9 rounded-md border border-input bg-card px-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-ring">
                                        @if ($argument->hint !== '')
                                            <p class="text-[10px] text-muted-foreground mt-0.5">{{ $argument->hint }}</p>
                                        @endif
                                    </div>
                                @endforeach

                                <div class="flex items-center gap-2">
                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-primary text-primary-foreground px-3 h-9 text-xs font-semibold hover:opacity-90">
                                        <i data-lucide="play" class="text-[13px]"></i> Run
                                    </button>
                                    <span class="hidden text-xs text-muted-foreground" data-al-playground-running>running…</span>
                                </div>

                                <pre class="hidden rounded-lg border border-border bg-muted/30 p-3 text-[11px] font-mono overflow-x-auto max-h-72 overflow-y-auto" data-al-playground-result></pre>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @push('scripts')
    <script>
        __alIcons();

        (function () {
            var links = document.querySelectorAll('[data-al-method-link]');
            var panels = {};
            document.querySelectorAll('[data-al-method-panel]').forEach(function (panel) {
                panels[panel.getAttribute('data-al-method-panel')] = panel;
            });

            var activeClasses = ['bg-brand/10', 'text-brand'];

            links.forEach(function (link) {
                link.addEventListener('click', function () {
                    var key = link.getAttribute('data-al-method-link');

                    Object.keys(panels).forEach(function (k) {
                        panels[k].classList.toggle('hidden', k !== key);
                    });

                    links.forEach(function (other) {
                        other.classList.remove.apply(other.classList, activeClasses);
                    });
                    link.classList.add.apply(link.classList, activeClasses);
                });
            });
        })();

        document.querySelectorAll('[data-al-playground-form]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var args = {};
                form.querySelectorAll('input[name]').forEach(function (input) {
                    if (input.value !== '') { args[input.name] = input.value; }
                });

                var running = form.querySelector('[data-al-playground-running]');
                var result = form.querySelector('[data-al-playground-result]');
                running.classList.remove('hidden');
                result.classList.add('hidden');

                fetch('{{ route('audit-log.playground.execute') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ method: form.getAttribute('data-method'), args: args })
                })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    result.textContent = JSON.stringify(payload.ok ? payload.result : payload, null, 2);
                })
                .catch(function (error) { result.textContent = String(error); })
                .finally(function () {
                    running.classList.add('hidden');
                    result.classList.remove('hidden');
                });
            });
        });
    </script>
    @endpush
@endsection
