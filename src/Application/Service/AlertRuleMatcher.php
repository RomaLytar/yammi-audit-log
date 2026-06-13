<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Service;

use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;

/**
 * Matches a recorded change against the configured alert rules. A rule names
 * a model class (required) and optionally the attributes and events it cares
 * about; empty lists mean "any".
 *
 * @internal
 */
final class AlertRuleMatcher
{
    /**
     * @param  list<array<string, mixed>>  $rules
     * @return list<array<string, mixed>> the rules the entry matches
     */
    public function matching(array $rules, TimelineEntryData $entry): array
    {
        $matched = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            if ($this->matches($rule, $entry)) {
                $matched[] = $rule;
            }
        }

        return $matched;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function matches(array $rule, TimelineEntryData $entry): bool
    {
        $model = $rule['model'] ?? null;

        if (! is_string($model) || $model === '' || $model !== $entry->auditableType) {
            return false;
        }

        $events = $this->stringList($rule['events'] ?? null);

        if ($events !== [] && ! in_array($entry->event, $events, true)) {
            return false;
        }

        $attributes = $this->stringList($rule['attributes'] ?? null);

        if ($attributes === []) {
            return true;
        }

        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $entry->changes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter($values, is_string(...)));
    }
}
