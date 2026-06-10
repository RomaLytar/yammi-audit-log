<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Actor;

use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;

/**
 * Holds the currently executing job and command so actor providers can attribute
 * a change to the work that caused it. Each job frame also carries the origin —
 * the actor that triggered the job — so a user -> job -> change chain is kept.
 * Jobs and commands are stacked to support nesting (a job dispatching another
 * job, a command calling another command).
 *
 * @internal
 */
final class ActorContext
{
    /** @var list<array{job: string, origin: ?Actor}> */
    private array $frames = [];

    /** @var list<string> */
    private array $commands = [];

    public function enterJob(string $jobClass, ?Actor $origin = null): void
    {
        $this->frames[] = ['job' => $jobClass, 'origin' => $origin];
    }

    public function leaveJob(): void
    {
        array_pop($this->frames);
    }

    public function currentJob(): ?string
    {
        $key = array_key_last($this->frames);

        return $key === null ? null : $this->frames[$key]['job'];
    }

    public function currentOrigin(): ?Actor
    {
        $key = array_key_last($this->frames);

        return $key === null ? null : $this->frames[$key]['origin'];
    }

    public function enterCommand(string $command): void
    {
        $this->commands[] = $command;
    }

    public function leaveCommand(): void
    {
        array_pop($this->commands);
    }

    public function currentCommand(): ?string
    {
        $key = array_key_last($this->commands);

        return $key === null ? null : $this->commands[$key];
    }
}
