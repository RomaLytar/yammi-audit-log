@extends('audit-log::layouts.app')

@section('title', 'Anomalies — Yammi')

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold tracking-tight flex items-center gap-2">
                <i data-lucide="siren" class="text-brand text-[20px]"></i> Anomalies
            </h1>
            <p class="text-sm text-muted-foreground mt-1">
                The audit log watching itself: change bursts, mass deletions and off-hours user activity in the selected window.
            </p>
        </div>
        <form method="GET" action="{{ route('audit-log.anomalies') }}" class="w-full sm:w-56">
            @include('audit-log::components.select', [
                'name' => 'window',
                'label' => 'Window',
                'value' => $model->window(),
                'options' => $model->windowOptions(),
                'placeholder' => 'Last 24 hours',
            ])
        </form>
    </div>

    <div class="mb-6 rounded-xl border border-border bg-card p-4 shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($model->rules() as $rule)
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-muted px-2.5 py-1 text-[11px] font-medium text-muted-foreground">
                        <i data-lucide="shield-check" class="text-[12px]"></i> {{ $rule }}
                    </span>
                @endforeach
            </div>
            <div class="flex items-center gap-3 text-xs">
                @if ($model->scanCron() !== null)
                    <span class="inline-flex items-center gap-1.5 text-muted-foreground" title="audit-log:detect-anomalies runs on this cron">
                        <i data-lucide="clock" class="text-[13px] text-success"></i> auto-scan <span class="font-mono">{{ $model->scanCron() }}</span>
                    </span>
                @endif
                <a href="{{ route('audit-log.settings.general') }}#section-anomaly-detection" class="text-brand hover:underline inline-flex items-center gap-1">
                    <i data-lucide="settings-2" class="text-[13px]"></i> Tune thresholds
                </a>
            </div>
        </div>
    </div>

    @if ($model->isEmpty())
        <div class="rounded-xl border border-border bg-card p-12 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-success/10 text-success">
                <i data-lucide="shield-check"></i>
            </div>
            <p class="text-sm font-medium">No anomalies in the {{ strtolower($model->windowLabel()) }}</p>
            <p class="text-xs text-muted-foreground mt-1">Every actor stayed under the thresholds and no off-hours user activity was recorded.</p>
        </div>
    @else
        <div class="mb-3 flex items-center gap-2 text-sm">
            <span class="inline-flex items-center gap-1.5 rounded-md bg-warning/10 px-2.5 py-1 text-xs font-semibold text-warning ring-1 ring-inset ring-warning/30">
                <i data-lucide="alert-triangle" class="text-[13px]"></i> {{ $model->count() }} {{ \Illuminate\Support\Str::plural('finding', $model->count()) }}
            </span>
            <span class="text-xs text-muted-foreground">in the {{ strtolower($model->windowLabel()) }}</span>
        </div>
        <div class="rounded-xl border border-border bg-card shadow-xs overflow-x-auto">
            <table class="w-full min-w-[680px] text-sm">
                <thead>
                    <tr class="bg-muted/40 text-[11px] uppercase tracking-wider text-muted-foreground text-left">
                        <th class="px-4 py-2.5 font-medium">Rule</th>
                        <th class="px-4 py-2.5 font-medium">Severity</th>
                        <th class="px-4 py-2.5 font-medium">Actor</th>
                        <th class="px-4 py-2.5 font-medium">Count</th>
                        <th class="px-4 py-2.5 font-medium">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($model->rows() as $row)
                        <tr class="{{ $loop->odd ? 'bg-muted/30' : 'bg-card' }}">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-{{ $row['tone'] }}/10 px-2 py-0.5 text-[11px] font-medium text-{{ $row['tone'] }} ring-1 ring-inset ring-{{ $row['tone'] }}/30">
                                    <i data-lucide="{{ $row['icon'] }}" class="text-[11px]"></i> {{ $row['rule'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-md bg-{{ $row['severityTone'] }}/10 px-2 py-0.5 text-[11px] font-medium text-{{ $row['severityTone'] }} ring-1 ring-inset ring-{{ $row['severityTone'] }}/30 capitalize">
                                    {{ $row['severity'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @include('audit-log::partials.actor-badge', ['type' => $row['actorType'], 'label' => $row['actorLabel']])
                            </td>
                            <td class="px-4 py-3 text-xs tabular-nums font-semibold">{{ $row['count'] }}</td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">{{ $row['description'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-[11px] text-muted-foreground">
            Each finding also fires the <span class="font-mono">AnomalyDetected</span> event when the scheduled scan runs — listen to it to push alerts into Slack or webhooks.
        </p>
    @endif
@endsection
