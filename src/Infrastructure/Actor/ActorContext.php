<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Actor;

/**
 * Holds the currently executing job and command so actor providers can attribute
 * a change to the work that caused it. Jobs are kept on a stack to support a job
 * dispatching another job synchronously.
 */
final class ActorContext
{
    /** @var list<string> */
    private array $jobs = [];

    private ?string $command = null;

    public function enterJob(string $jobClass): void
    {
        $this->jobs[] = $jobClass;
    }

    public function leaveJob(): void
    {
        array_pop($this->jobs);
    }

    public function currentJob(): ?string
    {
        $key = array_key_last($this->jobs);

        return $key === null ? null : $this->jobs[$key];
    }

    public function enterCommand(string $command): void
    {
        $this->command = $command;
    }

    public function currentCommand(): ?string
    {
        return $this->command;
    }
}
