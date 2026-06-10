<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Yammi\AuditLog\Application\Action\ListChangesAction;
use Yammi\AuditLog\Application\DTO\AuditFilterData;

final class DashboardController
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly ListChangesAction $listChanges,
    ) {}

    public function __invoke(Request $request): View
    {
        $filters = new AuditFilterData(
            type: $this->string($request->query('type')),
            event: $this->string($request->query('event')),
            actorType: $this->string($request->query('actor_type')),
            actor: $this->string($request->query('actor')),
            from: $this->string($request->query('from')),
            to: $this->string($request->query('to')),
            page: max(1, (int) $request->query('page', 1)),
        );

        return $this->view->make('audit-log::dashboard', [
            'list' => ($this->listChanges)($filters),
        ]);
    }

    private function string(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
