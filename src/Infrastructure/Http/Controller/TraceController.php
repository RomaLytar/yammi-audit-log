<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Yammi\AuditLog\Application\Action\BuildChainAction;
use Yammi\AuditLog\Presentation\ViewModel\TraceViewModel;

final class TraceController
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly BuildChainAction $buildChain,
    ) {}

    public function __invoke(string $correlation): View
    {
        $chain = ($this->buildChain)($correlation);

        if ($chain === null) {
            abort(404);
        }

        return $this->view->make('audit-log::trace', ['chain' => new TraceViewModel($chain)]);
    }
}
