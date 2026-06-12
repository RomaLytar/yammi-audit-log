<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml"
          href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0' stop-color='%238b5cf6'/%3E%3Cstop offset='1' stop-color='%236d28d9'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='32' height='32' rx='7' fill='url(%23g)'/%3E%3Cpath d='M16 8 v8 l5 3' fill='none' stroke='white' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3Cpath d='M8 16 a8 8 0 1 1 2.3 5.6' fill='none' stroke='white' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
    <title>@yield('title', 'Yammi — Change history')</title>

    <script>
        (function () {
            try {
                var stored = localStorage.getItem('al-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (!stored && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter var', 'Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                        mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'Consolas', 'monospace'],
                    },
                    colors: {
                        border: 'hsl(var(--border))',
                        input: 'hsl(var(--input))',
                        ring: 'hsl(var(--ring))',
                        background: 'hsl(var(--background))',
                        foreground: 'hsl(var(--foreground))',
                        primary: { DEFAULT: 'hsl(var(--primary))', foreground: 'hsl(var(--primary-foreground))' },
                        secondary: { DEFAULT: 'hsl(var(--secondary))', foreground: 'hsl(var(--secondary-foreground))' },
                        destructive: { DEFAULT: 'hsl(var(--destructive))', foreground: 'hsl(var(--destructive-foreground))' },
                        success: { DEFAULT: 'hsl(var(--success))', foreground: 'hsl(var(--success-foreground))' },
                        warning: { DEFAULT: 'hsl(var(--warning))', foreground: 'hsl(var(--warning-foreground))' },
                        info: { DEFAULT: 'hsl(var(--info))', foreground: 'hsl(var(--info-foreground))' },
                        muted: { DEFAULT: 'hsl(var(--muted))', foreground: 'hsl(var(--muted-foreground))' },
                        accent: { DEFAULT: 'hsl(var(--accent))', foreground: 'hsl(var(--accent-foreground))' },
                        popover: { DEFAULT: 'hsl(var(--popover))', foreground: 'hsl(var(--popover-foreground))' },
                        card: { DEFAULT: 'hsl(var(--card))', foreground: 'hsl(var(--card-foreground))' },
                        brand: { DEFAULT: 'hsl(var(--brand))', foreground: 'hsl(var(--brand-foreground))' },
                    },
                    borderRadius: {
                        xl: 'calc(var(--radius) + 4px)',
                        lg: 'var(--radius)',
                        md: 'calc(var(--radius) - 2px)',
                        sm: 'calc(var(--radius) - 4px)',
                    },
                    boxShadow: {
                        xs: '0 1px 2px 0 rgb(0 0 0 / 0.04)',
                        glow: '0 0 0 1px hsl(var(--ring) / 0.15), 0 8px 24px -8px hsl(var(--ring) / 0.25)',
                    },
                    keyframes: {
                        'fade-in': { from: { opacity: '0', transform: 'translateY(-2px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
                        'slide-down': { from: { opacity: '0', transform: 'translateY(-6px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
                        'pulse-soft': { '0%, 100%': { opacity: '1' }, '50%': { opacity: '0.55' } },
                    },
                    animation: {
                        'fade-in': 'fade-in .2s ease-out',
                        'slide-down': 'slide-down .22s ease-out',
                        'pulse-soft': 'pulse-soft 1.8s ease-in-out infinite',
                    },
                },
            },
        };
    </script>

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">

    <style>
        :root {
            --background: 0 0% 100%; --foreground: 240 10% 3.9%;
            --card: 0 0% 100%; --card-foreground: 240 10% 3.9%;
            --popover: 0 0% 100%; --popover-foreground: 240 10% 3.9%;
            --primary: 240 5.9% 10%; --primary-foreground: 0 0% 98%;
            --secondary: 240 4.8% 95.9%; --secondary-foreground: 240 5.9% 10%;
            --muted: 240 4.8% 95.9%; --muted-foreground: 240 3.8% 46.1%;
            --accent: 240 4.8% 95.9%; --accent-foreground: 240 5.9% 10%;
            --destructive: 0 72% 51%; --destructive-foreground: 0 0% 98%;
            --success: 142 71% 36%; --success-foreground: 0 0% 98%;
            --warning: 35 92% 45%; --warning-foreground: 48 96% 8%;
            --info: 217 91% 55%; --info-foreground: 0 0% 98%;
            --border: 240 5.9% 90%; --input: 240 5.9% 90%; --ring: 240 5.9% 10%;
            --brand: 262 83% 58%; --brand-foreground: 0 0% 100%; --radius: 0.625rem;
        }
        .dark {
            --background: 240 10% 4%; --foreground: 0 0% 98%;
            --card: 240 6% 7%; --card-foreground: 0 0% 98%;
            --popover: 240 6% 7%; --popover-foreground: 0 0% 98%;
            --primary: 0 0% 98%; --primary-foreground: 240 5.9% 10%;
            --secondary: 240 3.7% 15.9%; --secondary-foreground: 0 0% 98%;
            --muted: 240 3.7% 13%; --muted-foreground: 240 5% 65%;
            --accent: 240 3.7% 15.9%; --accent-foreground: 0 0% 98%;
            --destructive: 0 72% 55%; --destructive-foreground: 0 0% 98%;
            --success: 142 71% 45%; --success-foreground: 144 80% 8%;
            --warning: 35 92% 55%; --warning-foreground: 48 96% 8%;
            --info: 217 91% 60%; --info-foreground: 210 40% 98%;
            --border: 240 3.7% 16%; --input: 240 3.7% 18%; --ring: 240 4.9% 83.9%;
            --brand: 263 75% 70%; --brand-foreground: 240 10% 4%;
        }
        html, body { font-family: 'Inter var', 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif; }
        body { font-feature-settings: 'cv02','cv03','cv04','cv11'; -webkit-font-smoothing: antialiased; }
        *::-webkit-scrollbar { width: 10px; height: 10px; }
        *::-webkit-scrollbar-track { background: transparent; }
        *::-webkit-scrollbar-thumb { background: hsl(var(--border)); border-radius: 10px; border: 2px solid transparent; background-clip: padding-box; }
        [data-lucide] { width: 1em; height: 1em; stroke-width: 2; }

        .al-input {
            width: 100%; height: 2.25rem;
            border-radius: calc(var(--radius) - 2px);
            border: 1px solid hsl(var(--border));
            background: hsl(var(--card)); color: hsl(var(--foreground));
            padding: 0 0.6rem; font-size: 0.8125rem;
        }
        .al-input:focus { outline: none; box-shadow: 0 0 0 2px hsl(var(--ring) / 0.4); }
        .al-input--active { border-color: hsl(var(--brand) / 0.4); background: hsl(var(--brand) / 0.05); font-weight: 500; }

        .al-datefield input[type="date"] { color-scheme: light; }
        .dark .al-datefield input[type="date"] { color-scheme: dark; }
        .al-datefield input[type="date"]::-webkit-calendar-picker-indicator { opacity: 0; cursor: pointer; }
    </style>
</head>
<body class="bg-background text-foreground min-h-screen antialiased">

    <div aria-hidden="true" class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-[420px] overflow-hidden">
        <div class="absolute left-1/2 top-[-140px] h-[420px] w-[900px] -translate-x-1/2 rounded-full bg-brand/10 blur-3xl"></div>
    </div>

    <nav class="sticky top-0 z-40 backdrop-blur-md bg-background/75 border-b border-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14 items-center gap-4">
                <a href="{{ route('audit-log.dashboard') }}" class="flex items-center gap-2.5 shrink-0">
                    <div class="relative flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand to-brand/70 text-brand-foreground shadow-sm ring-1 ring-inset ring-white/10">
                        <i data-lucide="history" class="text-[15px]"></i>
                        <span class="absolute -right-0.5 -top-0.5 flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-pulse-soft rounded-full bg-success opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-success"></span>
                        </span>
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="font-semibold text-sm tracking-tight bg-gradient-to-r from-foreground to-brand bg-clip-text text-transparent">Yammi</span>
                        <span class="hidden sm:block text-[10px] text-muted-foreground -mt-0.5">Change history</span>
                    </div>
                </a>

                <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
                    @php $onLog = request()->routeIs('audit-log.dashboard') || request()->routeIs('audit-log.trace'); @endphp
                    <a href="{{ route('audit-log.dashboard') }}" title="Log"
                       class="inline-flex items-center gap-1.5 rounded-md px-2.5 sm:px-3 h-8 text-xs font-semibold border transition-colors {{ $onLog ? 'border-brand/30 bg-brand/10 text-brand' : 'border-border bg-card text-muted-foreground hover:text-foreground hover:bg-accent' }}">
                        <i data-lucide="list" class="text-[14px]"></i> <span class="hidden sm:inline">Log</span>
                    </a>
                    <a href="{{ route('audit-log.noise') }}" title="Noise"
                       class="inline-flex items-center gap-1.5 rounded-md px-2.5 sm:px-3 h-8 text-xs font-semibold border transition-colors {{ request()->routeIs('audit-log.noise') ? 'border-warning/40 bg-warning/10 text-warning' : 'border-border bg-card text-muted-foreground hover:text-foreground hover:bg-accent' }}">
                        <i data-lucide="alert-triangle" class="text-[14px]"></i> <span class="hidden sm:inline">Noise</span>
                        @if (($auditNoiseCount ?? 0) > 0)
                            <span class="inline-flex items-center justify-center min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-warning/20 text-warning text-[10px] font-bold tabular-nums">{{ $auditNoiseCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('audit-log.stats') }}" title="Stats"
                       class="inline-flex items-center gap-1.5 rounded-md px-2.5 sm:px-3 h-8 text-xs font-semibold border transition-colors {{ request()->routeIs('audit-log.stats') ? 'border-brand/30 bg-brand/10 text-brand' : 'border-border bg-card text-muted-foreground hover:text-foreground hover:bg-accent' }}">
                        <i data-lucide="bar-chart-3" class="text-[14px]"></i> <span class="hidden sm:inline">Stats</span>
                    </a>
                    <a href="{{ route('audit-log.time-machine') }}" title="Time machine"
                       class="inline-flex items-center gap-1.5 rounded-md px-2.5 sm:px-3 h-8 text-xs font-semibold border transition-colors {{ request()->routeIs('audit-log.time-machine') ? 'border-brand/30 bg-brand/10 text-brand' : 'border-border bg-card text-muted-foreground hover:text-foreground hover:bg-accent' }}">
                        <i data-lucide="calendar-clock" class="text-[14px]"></i> <span class="hidden lg:inline">Time machine</span>
                    </a>
                    <a href="{{ route('audit-log.settings') }}" title="Settings"
                       class="inline-flex items-center gap-1.5 rounded-md px-2.5 sm:px-3 h-8 text-xs font-semibold border transition-colors {{ request()->routeIs('audit-log.settings') || request()->routeIs('audit-log.settings.*') || request()->routeIs('audit-log.playground') ? 'border-brand/30 bg-brand/10 text-brand' : 'border-border bg-card text-muted-foreground hover:text-foreground hover:bg-accent' }}">
                        <i data-lucide="settings" class="text-[14px]"></i> <span class="hidden sm:inline">Settings</span>
                    </a>
                    <button type="button" onclick="__alToggleTheme()" title="Toggle theme"
                            class="inline-flex items-center justify-center h-8 w-8 shrink-0 rounded-md border border-border bg-card text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
                        <i data-lucide="sun-moon" class="text-[15px]"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 animate-fade-in">
        @yield('content')
    </main>

    <script>
        function __alToggleTheme() {
            var isDark = document.documentElement.classList.toggle('dark');
            try { localStorage.setItem('al-theme', isDark ? 'dark' : 'light'); } catch (e) {}
        }
        function __alIcons() {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        }
        function __alToggleRow(id) {
            var row = document.getElementById(id);
            if (row) { row.classList.toggle('hidden'); }
        }

        function __alCloseSelects(except) {
            document.querySelectorAll('[data-al-select]').forEach(function (sel) {
                if (sel === except) { return; }
                var dd = sel.querySelector('[data-al-select-dropdown]');
                var caret = sel.querySelector('[data-al-select-caret]');
                if (dd) { dd.classList.add('hidden'); }
                if (caret) { caret.classList.remove('rotate-180'); }
                var trigger = sel.querySelector('[data-al-select-trigger]');
                if (trigger) { trigger.setAttribute('aria-expanded', 'false'); }
            });
        }

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-al-select-trigger]');
            if (trigger) {
                var sel = trigger.closest('[data-al-select]');
                var dd = sel.querySelector('[data-al-select-dropdown]');
                var caret = sel.querySelector('[data-al-select-caret]');
                var willOpen = dd.classList.contains('hidden');
                __alCloseSelects(sel);
                if (willOpen) {
                    dd.classList.remove('hidden');
                    caret.classList.add('rotate-180');
                    trigger.setAttribute('aria-expanded', 'true');
                    __alIcons();
                } else {
                    dd.classList.add('hidden');
                    caret.classList.remove('rotate-180');
                }
                return;
            }

            var option = e.target.closest('[data-al-select-option]');
            if (option) {
                var host = option.closest('[data-al-select]');
                var input = host.querySelector('[data-al-select-input]');
                var labelEl = option.querySelector('[data-al-select-option-label]');
                input.value = option.getAttribute('data-value') || '';
                host.querySelector('[data-al-select-label]').textContent = labelEl ? labelEl.textContent.trim() : input.value;
                __alCloseSelects(null);
                var form = host.closest('form');
                if (form && !host.hasAttribute('data-al-select-nosubmit')) { form.requestSubmit ? form.requestSubmit() : form.submit(); }
                return;
            }

            if (!e.target.closest('[data-al-select]')) { __alCloseSelects(null); }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { __alCloseSelects(null); }
        });

        __alIcons();
    </script>
    @stack('scripts')
</body>
</html>
