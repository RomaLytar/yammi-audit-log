<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Query;

use Closure;
use Yammi\AuditLog\Application\DTO\Audit\ChangeListData;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;

/**
 * A fluent wrapper over AuditLog::changes([...]). It only assembles the same
 * filter array the array API already accepts and runs it through the same
 * parser/criteria, so there is no second query path or filter semantics.
 *
 * @internal
 */
final class AuditQueryBuilder
{
    /**
     * @var array<string, scalar>
     */
    private array $filters = [];

    /**
     * @param  Closure(array<string, scalar>): ChangeListData  $runner
     */
    public function __construct(
        private readonly Closure $runner,
    ) {}

    public function model(string $model): self
    {
        $this->filters['model'] = $model;

        return $this;
    }

    public function event(ChangeType|string $event): self
    {
        $this->filters['event'] = $event instanceof ChangeType ? $event->value : $event;

        return $this;
    }

    public function actorType(string $type): self
    {
        $this->filters['actor_type'] = $type;

        return $this;
    }

    public function actor(string $actor): self
    {
        $this->filters['actor'] = $actor;

        return $this;
    }

    public function id(string $id): self
    {
        $this->filters['id'] = $id;

        return $this;
    }

    public function field(string $field): self
    {
        $this->filters['field'] = $field;

        return $this;
    }

    /**
     * Value transition: combine with field() as field()->from(old)->to(new).
     */
    public function from(string $value): self
    {
        $this->filters['value_from'] = $value;

        return $this;
    }

    public function to(string $value): self
    {
        $this->filters['value_to'] = $value;

        return $this;
    }

    public function since(string $date): self
    {
        $this->filters['from'] = $date;

        return $this;
    }

    public function until(string $date): self
    {
        $this->filters['to'] = $date;

        return $this;
    }

    public function search(string $query): self
    {
        $this->filters['search'] = $query;

        return $this;
    }

    public function onPage(int $page): self
    {
        $this->filters['page'] = $page;

        return $this;
    }

    public function get(): ChangeListData
    {
        return ($this->runner)($this->filters);
    }
}
