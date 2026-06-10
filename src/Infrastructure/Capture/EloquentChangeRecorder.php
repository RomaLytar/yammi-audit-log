<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Capture;

use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface;
use Throwable;
use Yammi\AuditLog\Application\Action\RecordChangeAction;
use Yammi\AuditLog\Domain\Audit\Enum\ChangeType;

/** @internal */
final class EloquentChangeRecorder
{
    public function __construct(
        private readonly RecordChangeAction $action,
        private readonly ChangeDataFactory $factory,
        private readonly AuditableGuard $guard,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param  array<int, mixed>  $payload
     */
    public function handle(string $event, array $payload): void
    {
        $type = $this->changeType($event);

        if ($type === null) {
            return;
        }

        $model = $payload[0] ?? null;

        if (! $model instanceof Model || ! $this->guard->shouldAudit($model)) {
            return;
        }

        try {
            ($this->action)($this->factory->make($model, $type));
        } catch (Throwable $exception) {
            $this->logger->error(
                'Audit capture failed: '.$exception->getMessage(),
                ['exception' => $exception],
            );
        }
    }

    private function changeType(string $event): ?ChangeType
    {
        $prefix = strtok($event, ':');
        $verb = is_string($prefix) ? str_replace('eloquent.', '', $prefix) : '';

        return match ($verb) {
            'created' => ChangeType::Created,
            'updated' => ChangeType::Updated,
            'deleted' => ChangeType::Deleted,
            'restored' => ChangeType::Restored,
            default => null,
        };
    }
}
