<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Label;

use Illuminate\Database\Eloquent\Model;
use Throwable;
use Yammi\AuditLog\Application\Contract\LabelResolver;
use Yammi\AuditLog\Application\DTO\ChangeData;
use Yammi\AuditLog\Domain\Audit\ValueObject\LabelSnapshot;

/**
 * Snapshots human-readable labels for mapped foreign keys at event time, so
 * the dashboard can show "John Doe -> Jane Smith" next to "user_id: 5 -> 7"
 * even after the referenced row is changed or deleted. Models may expose a
 * getAuditLabel() method; otherwise common name attributes are used.
 *
 * @internal
 */
final class ConventionLabelResolver implements LabelResolver
{
    private const FALLBACK_ATTRIBUTES = ['name', 'title', 'email'];

    /**
     * @param  array<string, string>  $map  column => Eloquent model class
     */
    public function __construct(
        private readonly array $map,
    ) {}

    public function labelsFor(ChangeData $change): LabelSnapshot
    {
        if ($this->map === []) {
            return LabelSnapshot::empty();
        }

        $labels = [];

        foreach ($this->map as $field => $modelClass) {
            foreach ([[$change->before, 'old'], [$change->after, 'new']] as [$values, $suffix]) {
                $key = $values[$field] ?? null;

                if (! is_int($key) && ! is_string($key)) {
                    continue;
                }

                $label = $this->resolveLabel($modelClass, $key);

                if ($label !== null) {
                    $labels["{$field}.{$suffix}"] = $label;
                }
            }
        }

        return new LabelSnapshot($labels);
    }

    private function resolveLabel(string $modelClass, int|string $key): ?string
    {
        if (! is_a($modelClass, Model::class, true)) {
            return null;
        }

        try {
            $model = $modelClass::query()->find($key);
        } catch (Throwable) {
            return null;
        }

        if (! $model instanceof Model) {
            return null;
        }

        return $this->labelOf($model);
    }

    private function labelOf(Model $model): ?string
    {
        if (method_exists($model, 'getAuditLabel')) {
            $label = $model->getAuditLabel();

            return is_string($label) && $label !== '' ? $label : null;
        }

        foreach (self::FALLBACK_ATTRIBUTES as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
