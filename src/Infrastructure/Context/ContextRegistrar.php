<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Context;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Yammi\AuditLog\Application\Contract\ActorResolver;
use Yammi\AuditLog\Infrastructure\Actor\ActorContext;
use Yammi\AuditLog\Infrastructure\Actor\ActorSerializer;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;

/**
 * Maintains the actor and correlation context across jobs and commands, and
 * propagates origin and correlation into the queue payload so they survive the
 * queue boundary.
 *
 * @internal
 */
final class ContextRegistrar
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly ActorContext $actors,
        private readonly CorrelationContext $correlation,
        private readonly ActorSerializer $serializer,
        private readonly ActorResolver $resolver,
    ) {}

    public function register(): void
    {
        $actors = $this->actors;
        $correlation = $this->correlation;
        $serializer = $this->serializer;
        $resolver = $this->resolver;

        $this->events->listen(JobProcessing::class, static function (JobProcessing $event) use ($actors, $correlation, $serializer, $resolver): void {
            $payload = $event->job->payload();

            $origin = isset($payload['audit_origin']) && is_array($payload['audit_origin'])
                ? $serializer->fromArray($payload['audit_origin'])
                : $resolver->resolve();

            $actors->enterJob($event->job->resolveName(), $origin);

            $correlationId = isset($payload['audit_correlation']) && is_string($payload['audit_correlation'])
                ? $payload['audit_correlation']
                : ($correlation->current() ?? (string) Str::uuid());

            $correlation->push($correlationId);
        });

        $this->events->listen([JobProcessed::class, JobFailed::class], static function () use ($actors, $correlation): void {
            $actors->leaveJob();
            $correlation->pop();
        });

        $this->events->listen(CommandStarting::class, static function (CommandStarting $event) use ($actors, $correlation): void {
            $command = self::commandName($event->command);

            if ($command !== null) {
                $actors->enterCommand($command);
            }

            $correlation->push((string) Str::uuid());
        });

        $this->events->listen(CommandFinished::class, static function (CommandFinished $event) use ($actors, $correlation): void {
            if (self::commandName($event->command) !== null) {
                $actors->leaveCommand();
            }

            $correlation->pop();
        });

        $this->events->listen(ScheduledTaskStarting::class, static function (ScheduledTaskStarting $event) use ($actors): void {
            $actors->enterScheduledTask(self::scheduledTaskName($event->task));
        });

        $this->events->listen([ScheduledTaskFinished::class, ScheduledTaskFailed::class], static function () use ($actors): void {
            $actors->leaveScheduledTask();
        });

        Queue::createPayloadUsing(static function ($connection, $queue, $payload) use ($correlation, $serializer, $resolver): array {
            $extra = ['audit_correlation' => $correlation->current() ?? (string) Str::uuid()];

            $origin = $resolver->resolve();

            if (! $origin->isAnonymous()) {
                $extra['audit_origin'] = $serializer->toArray($origin);
            }

            return $extra;
        });
    }

    /**
     * The framework documents the event command as string, but dispatches null
     * when the command name cannot be resolved.
     */
    private static function commandName(mixed $command): ?string
    {
        return is_string($command) && $command !== '' ? $command : null;
    }

    private static function scheduledTaskName(ScheduledEvent $task): string
    {
        $description = $task->description;

        if (is_string($description) && $description !== '') {
            return $description;
        }

        $command = $task->command;

        if (is_string($command) && $command !== '') {
            $artisanCommand = preg_replace("/^.*['\"]?artisan['\"]?\s+/", '', $command);

            return is_string($artisanCommand) && $artisanCommand !== '' ? $artisanCommand : $command;
        }

        return 'scheduled task';
    }
}
