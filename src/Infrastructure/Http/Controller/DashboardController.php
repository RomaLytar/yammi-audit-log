<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

final class DashboardController
{
    public function __construct(
        private readonly ViewFactory $view,
    ) {}

    public function __invoke(Request $request): View
    {
        $filters = [
            'type' => $this->string($request->query('type')),
            'event' => $this->string($request->query('event')),
            'actor_type' => $this->string($request->query('actor_type')),
            'actor' => $this->string($request->query('actor')),
            'from' => $this->string($request->query('from')),
            'to' => $this->string($request->query('to')),
        ];

        $query = AuditRecordModel::query()
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        if ($filters['type'] !== '') {
            $query->where('auditable_type', $filters['type']);
        }

        if ($filters['event'] !== '') {
            $query->where('event', $filters['event']);
        }

        if ($filters['actor_type'] !== '') {
            $query->where('actor_type', $filters['actor_type']);
        }

        if ($filters['actor'] !== '') {
            $query->where('actor_label', 'like', '%'.$filters['actor'].'%');
        }

        if ($filters['from'] !== '') {
            $query->whereDate('occurred_at', '>=', $filters['from']);
        }

        if ($filters['to'] !== '') {
            $query->whereDate('occurred_at', '<=', $filters['to']);
        }

        $records = $query->paginate(25)->withQueryString();

        return $this->view->make('audit-log::dashboard', [
            'records' => $records,
            'filters' => $filters,
            'types' => $this->distinct('auditable_type'),
            'actorTypes' => $this->distinct('actor_type'),
            'events' => ['created', 'updated', 'deleted', 'restored'],
        ]);
    }

    /**
     * @return list<string>
     */
    private function distinct(string $column): array
    {
        $values = [];

        foreach (AuditRecordModel::query()->distinct()->orderBy($column)->pluck($column) as $value) {
            if (is_string($value) && $value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }

    private function string(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
