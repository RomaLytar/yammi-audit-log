<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Playground;

/**
 * The public facade surface, described for the playground page: what each
 * method does, its real-code example and the arguments the form needs.
 *
 * @internal
 */
final class MethodCatalog
{
    /**
     * @return list<PlaygroundMethodData>
     */
    public function all(): array
    {
        return [
            new PlaygroundMethodData(
                key: 'for',
                signature: 'AuditLog::for(Model|string $auditable, int|string|null $id = null, int $limit = 50): TimelineData',
                summary: 'Reads the change timeline of one record — every captured create/update/delete/restore with actor, origin, labels and correlation, newest first. Pass a model instance, or a class string plus id.',
                example: "use Yammi\\AuditLog\\Infrastructure\\Facade\\AuditLog;\n\n\$timeline = AuditLog::for(Order::class, 42);\n\nforeach (\$timeline->entries as \$entry) {\n    echo \"{\$entry->occurredAt} {\$entry->event} by {\$entry->actorLabel}\";\n}",
                arguments: [
                    new PlaygroundArgumentData('auditable_type', 'string', true, 'App\\Models\\Order', 'Fully-qualified model class (or morph alias).'),
                    new PlaygroundArgumentData('auditable_id', 'string', true, '42', 'The record key.'),
                    new PlaygroundArgumentData('limit', 'int', false, '50', 'Max entries, newest first.'),
                ],
            ),
            new PlaygroundMethodData(
                key: 'stateAt',
                signature: 'AuditLog::stateAt(Model|string $auditable, int|string|null $id = null, DateTimeImmutable|string|null $at = null): StateData',
                summary: 'The time machine: reconstructs the read-only attribute state one record had at any moment by folding its recorded diffs — what did this order look like on March 3rd? A date-only value means the end of that day; empty means now.',
                example: "use Yammi\\AuditLog\\Infrastructure\\Facade\\AuditLog;\n\n\$state = AuditLog::stateAt(Order::class, 42, '2026-03-03');\n\nif (\$state->existed) {\n    echo \$state->attributes['status'];\n}",
                arguments: [
                    new PlaygroundArgumentData('auditable_type', 'string', true, 'App\\Models\\Order', 'Fully-qualified model class (or morph alias).'),
                    new PlaygroundArgumentData('auditable_id', 'string', true, '42', 'The record key.'),
                    new PlaygroundArgumentData('at', 'string', false, '2026-03-03', 'Date or datetime; date-only means the end of that day. Empty = now.'),
                ],
            ),
            new PlaygroundMethodData(
                key: 'changes',
                signature: 'AuditLog::changes(array $filters = []): ChangeListData',
                summary: 'The dashboard list as data: filtered, paginated changes with totals, filter options and chain sizes — embed the audit log in your own admin. Filters: model, event, actor_type, actor, id, from, to, search, page, field, value_from, value_to.',
                example: "use Yammi\\AuditLog\\Infrastructure\\Facade\\AuditLog;\n\n\$list = AuditLog::changes([\n    'event' => 'updated',\n    'actor_type' => 'job',\n    'search' => 'refunded',\n]);\n\nforeach (\$list->entries as \$entry) {\n    echo \"{\$entry->occurredAt} {\$entry->actorLabel}: {\$entry->event}\";\n}",
                arguments: [
                    new PlaygroundArgumentData('event', 'string', false, 'updated', 'created, updated, deleted or restored.'),
                    new PlaygroundArgumentData('actor_type', 'string', false, 'job', 'user, job, command, scheduler or system.'),
                    new PlaygroundArgumentData('actor', 'string', false, 'Jane', 'Substring of the actor label.'),
                    new PlaygroundArgumentData('id', 'string', false, '42', 'Exact record key — combine with model for one record.'),
                    new PlaygroundArgumentData('search', 'string', false, 'refunded', 'Matches old/new values or the record id.'),
                    new PlaygroundArgumentData('page', 'int', false, '1', '25 entries per page.'),
                ],
            ),
            new PlaygroundMethodData(
                key: 'noise',
                signature: 'AuditLog::noise(array $filters = []): ChangeListData',
                summary: 'Only the flagged no-op writes (double saves where nothing real changed) — the Noise page as data. Takes the same filters as changes().',
                example: "use Yammi\\AuditLog\\Infrastructure\\Facade\\AuditLog;\n\n\$noise = AuditLog::noise();\n\necho \"{\$noise->total} suspicious double writes\";",
                arguments: [
                    new PlaygroundArgumentData('search', 'string', false, '', 'Matches old/new values or the record id.'),
                    new PlaygroundArgumentData('page', 'int', false, '1', '25 entries per page.'),
                ],
            ),
            new PlaygroundMethodData(
                key: 'chain',
                signature: 'AuditLog::chain(string $correlationId): ?ChainData',
                summary: 'The full cross-model change chain behind one correlation id — every record an HTTP request, command or job cascade produced, in order. Returns null when the id is unknown.',
                example: "use Yammi\\AuditLog\\Infrastructure\\Facade\\AuditLog;\n\n\$chain = AuditLog::chain(\$entry->correlationId);\n\necho \"{\$chain->count()} changes across {\$chain->modelCount} models,\";\necho \" started by {\$chain->rootActorLabel}\";",
                arguments: [
                    new PlaygroundArgumentData('correlation_id', 'string', true, 'paste a correlation id from the dashboard', 'Every entry on the dashboard carries one.'),
                ],
            ),
            new PlaygroundMethodData(
                key: 'stats',
                signature: 'AuditLog::stats(array $filters = []): StatsData',
                summary: 'The statistics page as data: totals, per-day rate, retention projection, breakdowns by event / actor type / model and the 30-day daily activity — narrowed by the same filters as changes().',
                example: "use Yammi\\AuditLog\\Infrastructure\\Facade\\AuditLog;\n\n\$stats = AuditLog::stats(['actor_type' => 'job']);\n\necho \"{\$stats->total} job-driven changes, ~{\$stats->perDay}/day\";",
                arguments: [
                    new PlaygroundArgumentData('event', 'string', false, '', 'created, updated, deleted or restored.'),
                    new PlaygroundArgumentData('actor_type', 'string', false, '', 'user, job, command, scheduler or system.'),
                    new PlaygroundArgumentData('search', 'string', false, '', 'Matches old/new values or the record id.'),
                ],
            ),
            new PlaygroundMethodData(
                key: 'anomalies',
                signature: 'AuditLog::anomalies(int|null $windowMinutes = null): list<AnomalyData>',
                summary: 'The anomaly scan as data: change bursts, mass deletions and off-hours user activity inside the look-back window — the Anomalies page as an array. Null = the configured anomalies.window_minutes.',
                example: "use Yammi\\AuditLog\\Infrastructure\\Facade\\AuditLog;\n\nforeach (AuditLog::anomalies(1440) as \$finding) {\n    echo \"[{\$finding->rule}] {\$finding->description}\";\n}",
                arguments: [
                    new PlaygroundArgumentData('window', 'int', false, '1440', 'Look-back window in minutes. Empty = the configured default.'),
                ],
            ),
            new PlaygroundMethodData(
                key: 'recordView',
                signature: 'AuditLog::recordView(Model|string $auditable, int|string|null $id = null): RecordViewData',
                summary: 'The single-record page as data: the record\'s own history plus changes of OTHER records connected to it — cascades it took part in (correlation chains) and diffs of other models whose <model>_id points at it.',
                example: "use Yammi\\AuditLog\\Infrastructure\\Facade\\AuditLog;\n\n\$view = AuditLog::recordView(Order::class, 42);\n\nforeach (\$view->related as \$related) {\n    echo \"{\$related->entry->auditableType} #{\$related->entry->auditableId} via {\$related->via}\";\n}",
                arguments: [
                    new PlaygroundArgumentData('auditable_type', 'string', true, 'App\\Models\\Order', 'Fully-qualified model class (or morph alias).'),
                    new PlaygroundArgumentData('auditable_id', 'string', true, '42', 'The record key.'),
                ],
            ),
            new PlaygroundMethodData(
                key: 'subjectReport',
                signature: 'AuditLog::subjectReport(Model|string $auditable, int|string|null $id = null): SubjectReportData',
                summary: 'The GDPR subject access report as data: every recorded change to one record PLUS every change that record performed as a user actor. The audit-log:subject-report command writes the same report to disk as NDJSON or HTML.',
                example: "use Yammi\\AuditLog\\Infrastructure\\Facade\\AuditLog;\n\n\$report = AuditLog::subjectReport(User::class, 5);\n\necho count(\$report->recordChanges).' changes to the record, ';\necho count(\$report->actorChanges).' made by them';",
                arguments: [
                    new PlaygroundArgumentData('auditable_type', 'string', true, 'App\\Models\\User', 'Fully-qualified model class (or morph alias) of the subject.'),
                    new PlaygroundArgumentData('auditable_id', 'string', true, '5', 'The subject key.'),
                ],
            ),
            new PlaygroundMethodData(
                key: 'record',
                signature: 'AuditLog::record(Model|string $auditable, int|string|null $id, ChangeType|string $event, array $before = [], array $after = []): ?TimelineEntryData',
                summary: 'Writes a change Eloquent events cannot see — mass ->update(), raw SQL, pivot sync(). Goes through the exact same pipeline as captured changes: secret redaction, actor attribution, FK labels and correlation. A no-op update returns null.',
                example: "use Yammi\\AuditLog\\Infrastructure\\Facade\\AuditLog;\n\nOrder::where('status', 'pending')->update(['status' => 'cancelled']);\n\nAuditLog::record(Order::class, \$order->id, 'updated',\n    before: ['status' => 'pending'],\n    after: ['status' => 'cancelled'],\n);",
                arguments: [
                    new PlaygroundArgumentData('auditable_type', 'string', true, 'App\\Models\\Order', 'Fully-qualified model class (or morph alias).'),
                    new PlaygroundArgumentData('auditable_id', 'string', true, '42', 'The record key.'),
                    new PlaygroundArgumentData('event', 'string', true, 'updated', 'created, updated, deleted or restored.'),
                    new PlaygroundArgumentData('before', 'json', false, '{"status": "pending"}', 'Attribute values before the change, as JSON.'),
                    new PlaygroundArgumentData('after', 'json', false, '{"status": "cancelled"}', 'Attribute values after the change, as JSON.'),
                ],
                destructive: true,
            ),
        ];
    }

    public function find(string $key): ?PlaygroundMethodData
    {
        foreach ($this->all() as $method) {
            if ($method->key === $key) {
                return $method;
            }
        }

        return null;
    }
}
