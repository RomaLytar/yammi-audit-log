<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Capture;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Application\DTO\Audit\ChangeData;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Infrastructure\Policy\AuditPolicyRegistry;

/** @internal */
final class ChangeDataFactory
{
    public function __construct(
        private readonly AuditPolicyRegistry $policies = new AuditPolicyRegistry,
    ) {}

    public function make(Model $model, ChangeType $event): ChangeData
    {
        [$before, $after] = match ($event) {
            ChangeType::Created => [[], $this->normalize($model->getAttributes())],
            ChangeType::Deleted => [$this->normalize($model->getAttributes()), []],
            default => [
                $this->normalize($this->originalOfChanged($model)),
                $this->normalize($model->getChanges()),
            ],
        };

        return new ChangeData(
            auditableType: $model->getMorphClass(),
            auditableId: (string) $model->getKey(),
            event: $event,
            before: $this->scopeToModel($model, $before),
            after: $this->scopeToModel($model, $after),
        );
    }

    /**
     * Models narrow their own audit surface: $auditInclude keeps only the
     * listed attributes, $auditExclude drops the listed ones entirely.
     *
     * @param  array<string, scalar|array<array-key, mixed>|null>  $attributes
     * @return array<string, scalar|array<array-key, mixed>|null>
     */
    private function scopeToModel(Model $model, array $attributes): array
    {
        $include = $this->stringList($model, 'auditInclude');

        if ($include !== []) {
            $attributes = array_intersect_key($attributes, array_flip($include));
        }

        $exclude = $this->stringList($model, 'auditExclude');

        if ($exclude !== []) {
            $attributes = array_diff_key($attributes, array_flip($exclude));
        }

        $policyIgnored = $this->policies->for($model)?->ignoredFields() ?? [];

        if ($policyIgnored !== []) {
            $attributes = array_diff_key($attributes, array_flip($policyIgnored));
        }

        return $attributes;
    }

    /**
     * @return list<string>
     */
    private function stringList(Model $model, string $property): array
    {
        $values = property_exists($model, $property) ? $model->{$property} : [];

        return is_array($values) ? array_values(array_filter($values, is_string(...))) : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function originalOfChanged(Model $model): array
    {
        $original = $model->getOriginal();

        return is_array($original)
            ? array_intersect_key($original, $model->getChanges())
            : [];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, scalar|array<array-key, mixed>|null>
     */
    private function normalize(array $attributes): array
    {
        $out = [];

        foreach ($attributes as $key => $value) {
            $out[(string) $key] = $this->value($value);
        }

        return $out;
    }

    /**
     * @return scalar|array<array-key, mixed>|null
     */
    private function value(mixed $value): string|int|float|bool|array|null
    {
        if ($value === null || is_scalar($value) || is_array($value)) {
            return $value;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        $encoded = json_encode($value);

        return $encoded === false ? null : $encoded;
    }
}
