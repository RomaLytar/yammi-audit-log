<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Yammi\AuditLog\Application\Action\ListChangesAction;
use Yammi\AuditLog\Infrastructure\Http\FilterFactory;

final class NoiseController
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly ListChangesAction $listChanges,
        private readonly FilterFactory $filters,
    ) {}

    public function __invoke(Request $request): View
    {
        return $this->view->make('audit-log::noise', [
            'list' => ($this->listChanges)($this->filters->fromRequest($request), onlyNoise: true),
        ]);
    }
}
