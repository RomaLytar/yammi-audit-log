<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Pipeline\Stage;

use Yammi\AuditLog\Application\Contract\LabelResolver;
use Yammi\AuditLog\Application\Pipeline\RecordChangeContext;
use Yammi\AuditLog\Application\Pipeline\RecordChangeStage;

/** @internal */
final class ResolveLabelsStage implements RecordChangeStage
{
    public function __construct(
        private readonly LabelResolver $resolver,
    ) {}

    public function __invoke(RecordChangeContext $context): RecordChangeContext
    {
        return $context->withLabels($this->resolver->labelsFor($context->change));
    }
}
