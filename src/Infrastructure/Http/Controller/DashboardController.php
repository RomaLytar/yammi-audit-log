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
        $query = AuditRecordModel::query()
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        $event = $request->query('event');

        if (is_string($event) && $event !== '') {
            $query->where('event', $event);
        }

        $records = $query->paginate(25)->withQueryString();

        return $this->view->make('audit-log::dashboard', ['records' => $records]);
    }
}
