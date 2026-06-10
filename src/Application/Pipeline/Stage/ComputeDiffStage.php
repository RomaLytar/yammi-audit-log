<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Pipeline\Stage;

use Yammi\AuditLog\Application\Contract\ValueRedactor;
use Yammi\AuditLog\Application\Pipeline\RecordChangeContext;
use Yammi\AuditLog\Application\Pipeline\RecordChangeStage;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;
use Yammi\AuditLog\Domain\Audit\ValueObject\FieldDiff;

/** @internal */
final class ComputeDiffStage implements RecordChangeStage
{
    /**
     * @param  list<string>  $ignoredAttributes
     */
    public function __construct(
        private readonly ValueRedactor $redactor,
        private readonly array $ignoredAttributes = [],
    ) {}

    public function __invoke(RecordChangeContext $context): RecordChangeContext
    {
        $raw = Diff::between($context->change->before, $context->change->after);
        $meaningful = $this->withoutIgnored($raw);

        // An update whose only changes are ignored attributes (e.g. timestamps)
        // changed nothing real — a no-op write, usually a double update. Keep it,
        // but flagged as noise and showing the raw change so it stays diagnosable.
        $isNoise = $context->change->event === ChangeType::Updated
            && $meaningful->isEmpty()
            && ! $raw->isEmpty();

        $diff = $isNoise ? $raw : $meaningful;

        if ($diff->isEmpty()) {
            return $context->withDiff($diff)->withNoise(false);
        }

        return $context->withDiff($this->redact($diff))->withNoise($isNoise);
    }

    private function withoutIgnored(Diff $diff): Diff
    {
        if ($this->ignoredAttributes === []) {
            return $diff;
        }

        $fields = [];

        foreach ($diff->fields() as $name => $field) {
            if (! in_array($name, $this->ignoredAttributes, true)) {
                $fields[] = $field;
            }
        }

        return $fields === [] ? Diff::empty() : Diff::fromFields($fields);
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
