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
