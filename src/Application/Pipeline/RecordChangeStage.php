<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Pipeline;

/** @internal */
interface RecordChangeStage
{
    public function __invoke(RecordChangeContext $context): RecordChangeContext;
}
