<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\ValueObject;

final class LabelSnapshot
{
    /** @param array<string, string> $labels */
    public function __construct(private readonly array $labels = []) {}

    public static function empty(): self
    {
        return new self([]);
    }

    public function isEmpty(): bool
    {
        return $this->labels === [];
    }

    public function for(string $field): ?string
    {
        return $this->labels[$field] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->labels;
    }
}
