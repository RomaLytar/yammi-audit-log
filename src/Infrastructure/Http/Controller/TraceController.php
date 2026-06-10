<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

final class TraceController
{
    public function __construct(
        private readonly ViewFactory $view,
    ) {}

    public function __invoke(string $correlation): View
    {
        $records = AuditRecordModel::query()
            ->where('correlation_id', $correlation)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        abort_if($records->isEmpty(), 404);

        return $this->view->make('audit-log::trace', [
            'correlation' => $correlation,
            'records' => $records,
        ]);
    }
}
