@php
    $lastPage = max(1, (int) ($lastPage ?? 1));
    $page = min($lastPage, max(1, (int) ($page ?? 1)));

    $numbers = array_values(array_unique(array_filter(
        [1, $page - 1, $page, $page + 1, $lastPage],
        static fn ($candidate) => $candidate >= 1 && $candidate <= $lastPage,
    )));
    sort($numbers);
@endphp

@if ($lastPage > 1)
    <nav class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-muted-foreground" aria-label="Pagination">
        <span class="tabular-nums">Page {{ $page }} of {{ $lastPage }}</span>

        <div class="flex flex-wrap items-center gap-1.5">
            @if ($page > 1)
                <a href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}" rel="prev"
                   class="inline-flex items-center gap-1 rounded-md border border-border bg-card px-2.5 h-8 hover:bg-accent">
                    <i data-lucide="chevron-left" class="text-[14px]"></i> Prev
                </a>
            @else
                <span class="inline-flex items-center gap-1 rounded-md border border-border bg-muted/30 px-2.5 h-8 opacity-50">
                    <i data-lucide="chevron-left" class="text-[14px]"></i> Prev
                </span>
            @endif

            @php $previous = 0; @endphp
            @foreach ($numbers as $number)
                @if ($number - $previous > 1)
                    <span class="px-1 select-none">…</span>
                @endif

                @if ($number === $page)
                    <span aria-current="page" class="inline-flex items-center justify-center min-w-8 h-8 px-2 rounded-md border border-brand/40 bg-brand/10 text-brand font-semibold tabular-nums">{{ $number }}</span>
                @else
                    <a href="{{ request()->fullUrlWithQuery(['page' => $number]) }}"
                       class="inline-flex items-center justify-center min-w-8 h-8 px-2 rounded-md border border-border bg-card hover:bg-accent tabular-nums">{{ $number }}</a>
                @endif

                @php $previous = $number; @endphp
            @endforeach

            @if ($page < $lastPage)
                <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}" rel="next"
                   class="inline-flex items-center gap-1 rounded-md border border-border bg-card px-2.5 h-8 hover:bg-accent">
                    Next <i data-lucide="chevron-right" class="text-[14px]"></i>
                </a>
            @else
                <span class="inline-flex items-center gap-1 rounded-md border border-border bg-muted/30 px-2.5 h-8 opacity-50">
                    Next <i data-lucide="chevron-right" class="text-[14px]"></i>
                </span>
            @endif

            <form method="GET" action="{{ url()->current() }}"
                  onsubmit="this.page.value = Math.max(1, Math.min({{ $lastPage }}, parseInt(this.page.value) || 1));"
                  class="ml-1 flex items-center gap-1">
                @foreach (request()->except('page') as $key => $value)
                    @if (is_array($value))
                        @foreach ($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <input type="number" name="page" min="1" max="{{ $lastPage }}" value="{{ $page }}"
                       aria-label="Go to page" class="al-input w-16 text-center tabular-nums">
                <button type="submit" class="inline-flex items-center rounded-md border border-border bg-card px-2.5 h-8 hover:bg-accent">Go</button>
            </form>
        </div>
    </nav>
@endif
