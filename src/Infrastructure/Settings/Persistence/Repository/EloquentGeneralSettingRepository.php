<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Settings\Persistence\Repository;

use Yammi\AuditLog\Domain\Settings\Repository\GeneralSettingRepository;
use Yammi\AuditLog\Infrastructure\Settings\Persistence\Eloquent\SettingModel;

/** @internal */
final class EloquentGeneralSettingRepository implements GeneralSettingRepository
{
    public function all(): array
    {
        $result = [];

        foreach (SettingModel::query()->get(['group', 'key', 'value']) as $row) {
            $group = $row->getAttribute('group');
            $key = $row->getAttribute('key');
            $value = $row->getAttribute('value');

            if (is_string($group) && is_string($key) && is_string($value)) {
                $result[$group][$key] = $value;
            }
        }

        return $result;
    }

    public function get(string $group, string $key): ?string
    {
        $row = SettingModel::query()
            ->where('group', $group)
            ->where('key', $key)
            ->first(['value']);

        $value = $row?->getAttribute('value');

        return is_string($value) ? $value : null;
    }

    public function set(string $group, string $key, string $value, string $type): void
    {
        SettingModel::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value, 'type' => $type],
        );
    }

    public function remove(string $group, string $key): void
    {
        SettingModel::query()
            ->where('group', $group)
            ->where('key', $key)
            ->delete();
    }
}
