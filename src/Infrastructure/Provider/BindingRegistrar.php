<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Provider;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;

/**
 * Base for the container-binding registrars the service provider delegates to.
 * Each subclass binds one cohesive slice of the graph; the shared config
 * normalisers live here so the bindings stay declarative.
 *
 * @internal
 */
abstract class BindingRegistrar
{
    public function __construct(
        protected readonly Application $app,
    ) {}

    abstract public function register(): void;

    protected function config(): ConfigRepository
    {
        return $this->app->make(ConfigRepository::class);
    }

    protected function auditTable(): string
    {
        $table = $this->config()->get('audit-log.database.table', 'audit_log');

        return is_string($table) ? $table : 'audit_log';
    }

    /**
     * @return list<string>
     */
    protected function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            if (is_string($item)) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    protected function classMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $field => $class) {
            if (is_string($field) && $field !== '' && is_string($class) && $class !== '') {
                $out[$field] = $class;
            }
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    protected function stringMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && $key !== '' && is_scalar($item)) {
                $out[$key] = (string) $item;
            }
        }

        return $out;
    }

    /**
     * @return list<int>
     */
    protected function hourRange(mixed $value): array
    {
        if (! is_array($value) || count($value) !== 2) {
            return [];
        }

        $hours = array_values($value);

        if (! is_numeric($hours[0]) || ! is_numeric($hours[1])) {
            return [];
        }

        $from = (int) $hours[0];
        $to = (int) $hours[1];

        return $from >= 0 && $from <= 23 && $to >= 0 && $to <= 23 ? [$from, $to] : [];
    }
}
