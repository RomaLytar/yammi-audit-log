<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Yammi\AuditLog\Application\Contract\AuditLogQuery;
use Yammi\AuditLog\Infrastructure\AuditLogManager;
use Yammi\AuditLog\Infrastructure\Support\AuditTimezone;
use Yammi\AuditLog\Presentation\ViewModel\TimeMachineViewModel;

/** @internal */
final class TimeMachineController
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly AuditLogManager $manager,
        private readonly AuditLogQuery $query,
        private readonly AuditTimezone $timezone,
    ) {}

    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'type' => 'sometimes|nullable|string|max:191',
            'id' => 'sometimes|nullable|string|max:64',
            'at' => 'sometimes|nullable|date',
        ]);

        $type = trim((string) ($validated['type'] ?? ''));
        $id = trim((string) ($validated['id'] ?? ''));
        $at = trim((string) ($validated['at'] ?? ''));

        $state = $type !== '' && $id !== ''
            ? new TimeMachineViewModel($this->manager->stateAt($type, $id, $at === '' ? null : $at), $this->timezone->name())
            : null;

        return $this->view->make('audit-log::time-machine', [
            'models' => $this->query->distinctModels(),
            'state' => $state,
            'type' => $type,
            'id' => $id,
            'at' => $at,
        ]);
    }
}
