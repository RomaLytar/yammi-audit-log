<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Pipeline;

final class RecordChangePipeline
{
    /**
     * @param  list<RecordChangeStage>  $stages
     */
    public function __construct(
        private readonly array $stages,
    ) {}

    public function process(RecordChangeContext $context): RecordChangeContext
    {
        foreach ($this->stages as $stage) {
            $context = $stage($context);
        }

        return $context;
    }
}
