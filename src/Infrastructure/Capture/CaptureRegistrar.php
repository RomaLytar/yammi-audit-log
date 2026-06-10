<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Capture;

use Illuminate\Contracts\Events\Dispatcher;

/**
 * Wires the global Eloquent listeners that feed model changes into the recorder.
 */
final class CaptureRegistrar
{
    public function __construct(
        private readonly Dispatcher $events,
    ) {}

    public function register(): void
    {
        foreach (['created', 'updated', 'deleted', 'restored'] as $verb) {
            $this->events->listen("eloquent.{$verb}: *", [EloquentChangeRecorder::class, 'handle']);
        }
    }
}
