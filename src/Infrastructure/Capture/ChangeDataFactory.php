<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Capture;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Yammi\AuditLog\Application\DTO\ChangeData;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;

final class ChangeDataFactory
{
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
            before: $before,
            after: $after,
        );
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
