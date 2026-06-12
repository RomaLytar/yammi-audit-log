<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Yammi\AuditLog\Infrastructure\AuditLogManager;
use Yammi\AuditLog\Infrastructure\Support\AuditTimezone;
use Yammi\AuditLog\Presentation\ViewModel\RecordViewModel;

/** @internal */
final class RecordController
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly AuditLogManager $manager,
        private readonly AuditTimezone $timezone,
    ) {}

    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'type' => 'required|string|max:191',
            'id' => 'required|string|max:64',
        ]);

        $type = trim((string) $validated['type']);
        $id = trim((string) $validated['id']);

        return $this->view->make('audit-log::record', [
            'record' => new RecordViewModel(
                $this->manager->recordView($type, $id),
                $this->timezone->name(),
            ),
            'type' => $type,
            'id' => $id,
        ]);
    }
}
