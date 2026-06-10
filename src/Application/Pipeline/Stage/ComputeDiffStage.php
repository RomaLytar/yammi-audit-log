<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Pipeline\Stage;

use Yammi\AuditLog\Application\Contract\ValueRedactor;
use Yammi\AuditLog\Application\Pipeline\RecordChangeContext;
use Yammi\AuditLog\Application\Pipeline\RecordChangeStage;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\FieldDiff;

final class ComputeDiffStage implements RecordChangeStage
{
    public function __construct(
        private readonly ValueRedactor $redactor,
    ) {}

    public function __invoke(RecordChangeContext $context): RecordChangeContext
    {
        $diff = Diff::between($context->change->before, $context->change->after);

        if ($diff->isEmpty()) {
            return $context->withDiff($diff);
        }

        return $context->withDiff($this->redact($diff));
    }

    private function redact(Diff $diff): Diff
    {
        $olds = [];
        $news = [];

        foreach ($diff->fields() as $name => $field) {
            $olds[$name] = $field->old;
            $news[$name] = $field->new;
        }

        $olds = $this->redactor->redact($olds);
        $news = $this->redactor->redact($news);

        $fields = [];

        foreach ($diff->fields() as $name => $field) {
            $fields[$name] = new FieldDiff($name, $olds[$name], $news[$name]);
        }

        return Diff::fromFields($fields);
    }
}
