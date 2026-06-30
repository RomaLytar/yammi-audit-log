<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use Illuminate\Console\Command;
use Yammi\AuditLog\Infrastructure\Retention\LegalHoldRegistry;

/**
 * Places, releases or lists legal holds. A held subject is exempt from
 * retention until the hold is released.
 *
 * @internal
 */
final class LegalHoldCommand extends Command
{
    protected $signature = 'audit-log:legal-hold {action : place, release or list} {model? : The auditable model class} {id? : The record id} {--reason= : Why the hold was placed}';

    protected $description = 'Place, release or list legal holds that exempt a subject from retention.';

    public function handle(LegalHoldRegistry $holds): int
    {
        $action = $this->string('action');

        if ($action === 'list') {
            return $this->list($holds);
        }

        $model = $this->string('model');
        $id = $this->string('id');

        if ($model === '' || $id === '') {
            $this->error('place and release need a model class and an id.');

            return self::FAILURE;
        }

        if ($action === 'place') {
            $reason = $this->option('reason');
            $holds->place($model, $id, is_string($reason) ? $reason : null);
            $this->info("Legal hold placed on {$model} #{$id}.");

            return self::SUCCESS;
        }

        if ($action === 'release') {
            $released = $holds->release($model, $id);
            $this->info($released ? "Legal hold released on {$model} #{$id}." : "No legal hold on {$model} #{$id}.");

            return self::SUCCESS;
        }

        $this->error('Unknown action. Use place, release or list.');

        return self::FAILURE;
    }

    private function list(LegalHoldRegistry $holds): int
    {
        $rows = $holds->all();

        if ($rows === []) {
            $this->info('No legal holds.');

            return self::SUCCESS;
        }

        $this->table(
            ['Model', 'Id', 'Reason', 'Placed at'],
            array_map(static fn ($hold): array => [$hold->model(), $hold->auditableId, $hold->reason ?? '', $hold->placedAt ?? ''], $rows),
        );

        return self::SUCCESS;
    }

    private function string(string $name): string
    {
        $value = $this->argument($name);

        return is_string($value) ? $value : '';
    }
}
