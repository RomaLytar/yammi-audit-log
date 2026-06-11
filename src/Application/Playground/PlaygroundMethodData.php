<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Playground;

/** @internal */
final class PlaygroundMethodData
{
    /**
     * @param  list<PlaygroundArgumentData>  $arguments
     */
    public function __construct(
        public readonly string $key,
        public readonly string $signature,
        public readonly string $summary,
        public readonly string $example,
        public readonly array $arguments,
        public readonly bool $destructive = false,
    ) {}
}
