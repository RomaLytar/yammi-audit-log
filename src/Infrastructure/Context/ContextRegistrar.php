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
use Yammi\AuditLog\Application\Contract\Resolver\ActorResolver;
use Yammi\AuditLog\Domain\Audit\ValueObject\Span;
use Yammi\AuditLog\Infrastructure\Actor\ActorContext;
use Yammi\AuditLog\Infrastructure\Actor\ActorSerializer;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;
use Yammi\AuditLog\Infrastructure\Correlation\SpanContext;
use Yammi\AuditLog\Infrastructure\Correlation\TraceContext;

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
        private readonly SpanContext $spans,
        private readonly TraceContext $traces,
    ) {}

    public function register(): void
    {
        $actors = $this->actors;
        $correlation = $this->correlation;
        $serializer = $this->serializer;
        $resolver = $this->resolver;
        $spans = $this->spans;
        $traces = $this->traces;

        $this->events->listen(JobProcessing::class, static function (JobProcessing $event) use ($actors, $correlation, $spans, $traces, $serializer, $resolver): void {
            $payload = $event->job->payload();

            $origin = isset($payload['audit_origin']) && is_array($payload['audit_origin'])
                ? $serializer->fromArray($payload['audit_origin'])
                : $resolver->resolve();

            $actors->enterJob($event->job->resolveName(), $origin);

            $correlationId = isset($payload['audit_correlation']) && is_string($payload['audit_correlation'])
                ? $payload['audit_correlation']
                : ($correlation->current() ?? (string) Str::uuid());

            $correlation->push($correlationId);

            $parentSpan = isset($payload['audit_parent_span']) && is_string($payload['audit_parent_span'])
                ? $payload['audit_parent_span']
                : $spans->current()?->id;

            $spans->push(new Span((string) Str::uuid(), $parentSpan));

            $trace = isset($payload['audit_trace']) && is_string($payload['audit_trace'])
                ? $payload['audit_trace']
                : $traces->current();

            $traces->push($trace);
        });

        $this->events->listen([JobProcessed::class, JobFailed::class], static function () use ($actors, $correlation, $spans, $traces): void {
            $actors->leaveJob();
            $correlation->pop();
            $spans->pop();
            $traces->pop();
        });

        $this->events->listen(CommandStarting::class, static function (CommandStarting $event) use ($actors, $correlation, $spans, $traces): void {
            $command = self::commandName($event->command);

            if ($command !== null) {
                $actors->enterCommand($command);
            }

            $correlation->push((string) Str::uuid());
            $spans->push(new Span((string) Str::uuid(), $spans->current()?->id));
            $traces->push($traces->current());
        });

        $this->events->listen(CommandFinished::class, static function (CommandFinished $event) use ($actors, $correlation, $spans, $traces): void {
            if (self::commandName($event->command) !== null) {
                $actors->leaveCommand();
            }

            $correlation->pop();
            $spans->pop();
            $traces->pop();
        });

        $this->events->listen(ScheduledTaskStarting::class, static function (ScheduledTaskStarting $event) use ($actors): void {
            $actors->enterScheduledTask(self::scheduledTaskName($event->task));
        });

        $this->events->listen([ScheduledTaskFinished::class, ScheduledTaskFailed::class], static function () use ($actors): void {
            $actors->leaveScheduledTask();
        });

        Queue::createPayloadUsing(static function ($connection, $queue, $payload) use ($correlation, $spans, $traces, $serializer, $resolver): array {
            $extra = ['audit_correlation' => $correlation->current() ?? (string) Str::uuid()];

            $origin = $resolver->resolve();

            if (! $origin->isAnonymous()) {
                $extra['audit_origin'] = $serializer->toArray($origin);
            }

            $currentSpan = $spans->current();

            if ($currentSpan !== null) {
                $extra['audit_parent_span'] = $currentSpan->id;
            }

            $currentTrace = $traces->current();

            if ($currentTrace !== null) {
                $extra['audit_trace'] = $currentTrace;
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
