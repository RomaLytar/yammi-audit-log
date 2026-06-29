@php $focus = $focus ?? null; @endphp
<li>
    <div class="al-node al-node--{{ $node->actorType() }}{{ $node->isRoot() ? ' al-node--root' : '' }}">
        <div class="al-node__head">
            <span class="al-node__proc"><i data-lucide="{{ $node->processIcon() }}"></i> {{ $node->processLabel() }}</span>
            @if ($node->isRoot())
                <span class="al-node__flag"><i data-lucide="flag"></i> Root</span>
            @endif
        </div>

        <div class="al-node__actor" title="{{ $node->actorLabel() }}">{{ \Illuminate\Support\Str::afterLast($node->actorLabel(), '\\') }}</div>

        @if ($node->originLabel())
            <div class="al-node__from"><i data-lucide="corner-down-right"></i> from {{ \Illuminate\Support\Str::afterLast($node->originLabel(), '\\') }}</div>
        @endif

        <div class="al-node__body">
            @foreach ($node->entries as $entry)
                @php
                    $isFocus = $focus !== null && $entry->recordId() === $focus;
                    $diffId = 'al-trace-diff-'.($entry->recordId() ?? '0');
                @endphp
                <button type="button" class="al-node__entry{{ $isFocus ? ' al-node__entry--focus' : '' }}"
                        @if ($isFocus) id="al-focus-entry" @endif
                        @if ($entry->changeCount() > 0) onclick="__alToggleRow('{{ $diffId }}')" @endif>
                    <span class="al-node__dot al-node__dot--{{ $entry->event() }}"></span>
                    <span class="al-node__entry-model">{{ $entry->model() }} <span class="al-node__entry-id">#{{ $entry->id() }}</span></span>
                    @include('audit-log::partials.event-badge', ['event' => $entry->event()])
                    @if ($entry->changeCount() > 0)
                        <span class="al-node__entry-fields">{{ $entry->changeCount() }} {{ \Illuminate\Support\Str::plural('field', $entry->changeCount()) }}</span>
                    @endif
                    @if ($isFocus)
                        <span class="al-node__here"><i data-lucide="map-pin"></i> You came from here</span>
                    @endif
                </button>
                @if ($entry->changeCount() > 0)
                    <div id="{{ $diffId }}" class="al-node__diff{{ $isFocus ? '' : ' hidden' }}">
                        <table>
                            <thead><tr><th>Field</th><th>Old</th><th>New</th></tr></thead>
                            <tbody>
                                @foreach ($entry->changes() as $change)
                                    <tr>
                                        <td>{{ $change['field'] }}</td>
                                        <td class="al-old">{{ $change['old'] }}</td>
                                        <td class="al-new">{{ $change['new'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    @if ($node->hasChildren())
        <ul>
            @foreach ($node->children as $child)
                @include('audit-log::partials.trace-node', ['node' => $child, 'focus' => $focus])
            @endforeach
        </ul>
    @endif
</li>
