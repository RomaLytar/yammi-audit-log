<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yammi\AuditLog\Infrastructure\Transfer\DatabaseTransferRunner;

/** @internal */
final class DatabaseTransferController
{
    public function __invoke(Request $request, DatabaseTransferRunner $runner): RedirectResponse
    {
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
