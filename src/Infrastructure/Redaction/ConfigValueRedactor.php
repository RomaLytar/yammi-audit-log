<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Redaction;

use Yammi\AuditLog\Application\Contract\ValueRedactor;

/** @internal */
final class ConfigValueRedactor implements ValueRedactor
{
    /**
     * @param  list<string>  $secretKeys
     */
    public function __construct(
        private readonly array $secretKeys,
        private readonly string $placeholder = '[redacted]',
    ) {}

    public function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($this->isSecret($key)) {
                $values[$key] = $this->placeholder;
            } elseif (is_array($value)) {
                $values[$key] = $this->redactNested($value);
            }
        }

        return $values;
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private function redactNested(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($this->isSecret((string) $key)) {
                $values[$key] = $this->placeholder;
            } elseif (is_array($value)) {
                $values[$key] = $this->redactNested($value);
            }
        }

        return $values;
    }

    private function isSecret(string $key): bool
    {
        $normalized = strtolower($key);

        foreach ($this->secretKeys as $needle) {
            if ($needle !== '' && str_contains($normalized, strtolower($needle))) {
                return true;
            }
        }

        return false;
    }
}
