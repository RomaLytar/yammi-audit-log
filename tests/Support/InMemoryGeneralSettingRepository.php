<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use Yammi\AuditLog\Domain\Settings\Repository\GeneralSettingRepository;

final class InMemoryGeneralSettingRepository implements GeneralSettingRepository
{
    /** @var array<string, array<string, string>> */
    public array $stored = [];

    public function all(): array
    {
        return $this->stored;
    }

    public function get(string $group, string $key): ?string
    {
        return $this->stored[$group][$key] ?? null;
    }

    public function set(string $group, string $key, string $value, string $type): void
    {
        $this->stored[$group][$key] = $value;
    }

    public function remove(string $group, string $key): void
    {
        unset($this->stored[$group][$key]);
    }
}
