<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Playground;

/** @internal */
final class PlaygroundArgumentData
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly bool $required,
        public readonly string $placeholder,
        public readonly string $hint = '',
    ) {}
}
