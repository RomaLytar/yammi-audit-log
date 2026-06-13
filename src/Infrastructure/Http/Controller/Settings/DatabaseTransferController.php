<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller\Settings;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yammi\AuditLog\Infrastructure\Transfer\DatabaseTransferRunner;

/** @internal */
final class DatabaseTransferController
{
    public function __construct(
        private readonly Gate $gate,
        private readonly ConfigRepository $config,
    ) {}

    public function __invoke(Request $request, DatabaseTransferRunner $runner): RedirectResponse
    {
        $ability = $this->config->get('audit-log.transfer.gate');

        if (is_string($ability) && $ability !== '' && $this->gate->denies($ability)) {
            return redirect()
                ->route('audit-log.settings.database')
                ->with('audit_log_error', 'You are not authorized to transfer audit data.');
        }

        $validated = $request->validate([
            'from' => 'required|string',
            'to' => 'required|string|different:from',
            'delete_source' => 'sometimes|boolean',
        ]);

        $result = $runner->run(
            is_string($validated['from']) ? $validated['from'] : '',
            is_string($validated['to']) ? $validated['to'] : '',
            $request->boolean('delete_source'),
        );

        return redirect()
            ->route('audit-log.settings.database')
            ->with(
                $result->ok ? 'audit_log_status' : 'audit_log_error',
                $result->message,
            );
    }
}
