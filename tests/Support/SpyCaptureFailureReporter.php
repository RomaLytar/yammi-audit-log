<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Throwable;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;
use Yammi\AuditLog\Infrastructure\Capture\CaptureFailureReporter;

final class SpyCaptureFailureReporter implements CaptureFailureReporter
{
    /** @var list<array{model: ?Model, event: ?ChangeType, exception: Throwable}> */
    public array $reported = [];

    public function record(?Model $model, ?ChangeType $event, Throwable $exception): void
    {
        $this->reported[] = ['model' => $model, 'event' => $event, 'exception' => $exception];
    }
}
