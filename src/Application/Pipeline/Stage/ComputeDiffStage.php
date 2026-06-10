<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Pipeline\Stage;

use Yammi\AuditLog\Application\Contract\ValueRedactor;
use Yammi\AuditLog\Application\Pipeline\RecordChangeContext;
use Yammi\AuditLog\Application\Pipeline\RecordChangeStage;
use Yammi\AuditLog\Domain\Audit\ValueObject\Diff;

final class ComputeDiffStage implements RecordChangeStage
{
    public function __construct(
        private readonly ValueRedactor $redactor,
    ) {}

    public function __invoke(RecordChangeContext $context): RecordChangeContext
    {
        $before = $this->redactor->redact($context->change->before);
        $after = $this->redactor->redact($context->change->after);

        return $context->withDiff(Diff::between($before, $after));
    }
}
