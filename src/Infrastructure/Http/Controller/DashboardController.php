<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Yammi\AuditLog\Application\Action\ListChangesAction;
use Yammi\AuditLog\Infrastructure\Http\FilterFactory;
use Yammi\AuditLog\Presentation\ViewModel\DashboardViewModel;

/** @internal */
final class DashboardController
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly ListChangesAction $listChanges,
        private readonly FilterFactory $filters,
    ) {}

    public function __invoke(Request $request): View
    {
        $list = ($this->listChanges)($this->filters->fromRequest($request));

        return $this->view->make('audit-log::dashboard', [
            'list' => new DashboardViewModel($list),
        ]);
    }
}
