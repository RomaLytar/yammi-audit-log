<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Actor;

use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

/**
 * Serialises an actor into a job payload so the origin survives the queue
 * boundary and a worker can attribute a change back to who triggered the job.
 *
 * @internal
 */
final class ActorSerializer
{
    /**
     * @return array{type: string, identifier: ?string, label: ?string}
     */
    public function toArray(Actor $actor): array
    {
        return [
            'type' => $actor->type->value,
            'identifier' => $actor->identifier,
            'label' => $actor->label,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function fromArray(array $data): ?Actor
    {
        $type = $data['type'] ?? null;

        if (! is_string($type)) {
            return null;
        }

        $actorType = ActorType::tryFrom($type);

        if ($actorType === null) {
            return null;
        }

        $identifier = $data['identifier'] ?? null;
        $label = $data['label'] ?? null;

        return new Actor(
            $actorType,
            is_string($identifier) ? $identifier : null,
            is_string($label) ? $label : null,
        );
    }
}
