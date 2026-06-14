<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller\Ui;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Yammi\AuditLog\Infrastructure\AuditLogManager;
use Yammi\AuditLog\Infrastructure\Support\AuditTimezone;

/**
 * Renders one subject's read-only activity feed behind a temporary signed URL.
 * The signature (validated by the route's middleware) is the access grant —
 * no admin auth — so a tenant or user can see their own "Account activity".
 *
 * @internal
 */
final class ScopedActivityController
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

        return $this->view->make('audit-log::activity', [
            'report' => $this->manager->subjectReport(trim((string) $validated['type']), trim((string) $validated['id'])),
            'timezone' => $this->timezone->name(),
        ]);
    }
}
