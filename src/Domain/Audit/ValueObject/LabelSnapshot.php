<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Domain\Audit\ValueObject;

final class LabelSnapshot
{
    private const MAX_LENGTH = 191;

    /** @var array<string, string> */
    private readonly array $labels;

    /** @param array<string, string> $labels */
    public function __construct(array $labels = [])
    {
        $normalized = [];

        foreach ($labels as $field => $label) {
            $normalized[$field] = mb_substr($label, 0, self::MAX_LENGTH);
        }

        $this->labels = $normalized;
    }

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
