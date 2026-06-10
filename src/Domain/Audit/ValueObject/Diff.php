<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\ValueObject;

final class Diff
{
    private const MAX_FIELDS = 250;

    /** @var array<string, FieldDiff> */
    private readonly array $fields;

    /** @param array<string, FieldDiff> $fields */
    private function __construct(array $fields)
    {
        $this->fields = array_slice($fields, 0, self::MAX_FIELDS, true);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param  array<array-key, FieldDiff>  $fields
     */
    public static function fromFields(array $fields): self
    {
        $indexed = [];

        foreach ($fields as $field) {
            $indexed[$field->field] = $field;
        }

        return new self($indexed);
    }

    /**
     * @param  array<string, scalar|array<array-key, mixed>|null>  $before
     * @param  array<string, scalar|array<array-key, mixed>|null>  $after
     */
    public static function between(array $before, array $after): self
    {
        $fields = [];

        /** @var list<string> $keys */
        $keys = array_values(array_unique(array_merge(array_keys($before), array_keys($after))));

        foreach ($keys as $key) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;

            if ($old !== $new) {
                $fields[$key] = new FieldDiff($key, $old, $new);
            }
        }

        return new self($fields);
    }

    public function isEmpty(): bool
    {
        return $this->fields === [];
    }

    public function count(): int
    {
        return count($this->fields);
    }

    public function has(string $field): bool
    {
        return isset($this->fields[$field]);
    }

    public function field(string $field): ?FieldDiff
    {
        return $this->fields[$field] ?? null;
    }

    /**
     * @return array<string, FieldDiff>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * @return array<string, array{old: scalar|array<array-key, mixed>|null, new: scalar|array<array-key, mixed>|null}>
     */
    public function toArray(): array
    {
        $out = [];

        foreach ($this->fields as $name => $field) {
            $out[$name] = ['old' => $field->old, 'new' => $field->new];
        }

        return $out;
    }
}
