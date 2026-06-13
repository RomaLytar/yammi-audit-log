<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Yammi\AuditLog\Application\Action\BuildSubjectReportAction;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\DTO\SubjectReportData;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Infrastructure\Console\Support\ArchiveDisk;

/** @internal */
final class SubjectReportCommand extends Command
{
    protected $signature = 'audit-log:subject-report
                            {model : Fully-qualified model class (or morph alias) of the subject}
                            {id : The subject key}
                            {--format=ndjson : ndjson or html}
                            {--disk= : Filesystem disk to write to (defaults to audit-log.archive.disk)}';

    protected $description = 'Write a GDPR subject access report: every change to the record plus every change made by it';

    public function handle(
        BuildSubjectReportAction $build,
        ConfigRepository $config,
        FilesystemFactory $storage,
        ViewFactory $view,
        Clock $clock,
    ): int {
        $formatOption = $this->option('format');
        $format = strtolower(trim(is_string($formatOption) ? $formatOption : ''));

        if (! in_array($format, ['ndjson', 'html'], true)) {
            $this->error('Format must be ndjson or html.');

            return self::FAILURE;
        }

        $modelArgument = $this->argument('model');
        $idArgument = $this->argument('id');
        $model = trim(is_string($modelArgument) ? $modelArgument : '');
        $id = trim(is_string($idArgument) ? $idArgument : '');

        $report = $build(AuditableReference::to($model, $id));

        $disk = $storage->disk((new ArchiveDisk)->name($this->option('disk'), $config));

        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $report->model().'-'.$id));
        $path = 'audit-log/subject-report-'.$slug.'-'.$clock->now()->format('Ymd-His').'.'.$format;

        $disk->put($path, $format === 'html'
            ? $view->make('audit-log::subject-report', ['report' => $report])->render()
            : $this->ndjson($report));

        $this->info(sprintf(
            'Subject report for %s #%s: %d change(s) to the record, %d made by it.',
            $report->auditableType,
            $id,
            count($report->recordChanges),
            count($report->actorChanges),
        ));
        $this->info("Written to {$path}.");

        if ($report->truncated) {
            $this->warn('A section hit the '.BuildSubjectReportAction::SECTION_LIMIT.'-change cap; the report may be incomplete.');
        }

        return self::SUCCESS;
    }

    private function ndjson(SubjectReportData $report): string
    {
        $lines = [(string) json_encode([
            'report' => 'subject-access',
            'subject' => $report->auditableType.'#'.$report->auditableId,
            'generated_at' => $report->generatedAt,
        ])];

        foreach ($report->recordChanges as $entry) {
            $lines[] = (string) json_encode(['section' => 'record_changes'] + (array) $entry);
        }

        foreach ($report->actorChanges as $entry) {
            $lines[] = (string) json_encode(['section' => 'actor_changes'] + (array) $entry);
        }

        return implode("\n", $lines)."\n";
    }
}
