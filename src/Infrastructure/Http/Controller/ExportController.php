<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yammi\AuditLog\Application\Action\ExportChangesAction;
use Yammi\AuditLog\Infrastructure\Http\FilterFactory;
use Yammi\AuditLog\Presentation\Export\ChangeCsvPresenter;

/** @internal */
final class ExportController
{
    public function __construct(
        private readonly ExportChangesAction $export,
        private readonly FilterFactory $filters,
        private readonly ChangeCsvPresenter $presenter,
    ) {}

    public function __invoke(Request $request): StreamedResponse|JsonResponse
    {
        $entries = ($this->export)($this->filters->fromRequest($request));

        if ($request->query('format') === 'json') {
            $rows = [];

            foreach ($entries as $entry) {
                $rows[] = $this->presenter->jsonRow($entry);
            }

            return new JsonResponse(
                ['data' => $rows, 'count' => count($rows)],
                200,
                ['Content-Disposition' => 'attachment; filename="audit-log.json"'],
            );
        }

        return new StreamedResponse(function () use ($entries): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fputcsv($out, $this->presenter->headings());

            foreach ($entries as $entry) {
                fputcsv($out, $this->presenter->row($entry));
            }

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="audit-log.csv"',
        ]);
    }
}
