<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Integrity;

use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditDigestModel;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;

/**
 * Captures and signs a digest of the current audit chain: its head hash,
 * record count and time span at this moment.
 *
 * @internal
 */
final class DigestRecorder
{
    public function __construct(
        private readonly IntegritySigner $signer,
        private readonly DigestPayload $payload,
        private readonly Clock $clock,
    ) {}

    public function record(): AuditDigestModel
    {
        $head = AuditRecordModel::query()->withoutGlobalScopes()->orderByDesc('id')->value('integrity_hash');
        $head = is_string($head) && $head !== '' ? $head : null;

        $count = AuditRecordModel::query()->withoutGlobalScopes()->count();
        $start = $this->stringValue(AuditRecordModel::query()->withoutGlobalScopes()->min('occurred_at'));
        $end = $this->stringValue(AuditRecordModel::query()->withoutGlobalScopes()->max('occurred_at'));
        $generatedAt = $this->clock->now()->format('Y-m-d H:i:s');

        $signature = $this->signer->sign($this->payload->canonical($head, $count, $start, $end, $generatedAt));

        $model = new AuditDigestModel;
        $model->forceFill([
            'chain_head' => $head,
            'record_count' => $count,
            'range_start' => $start,
            'range_end' => $end,
            'generated_at' => $generatedAt,
            'algorithm' => $signature !== null ? $this->signer->algorithm() : null,
            'signature' => $signature,
            'created_at' => $generatedAt,
        ]);
        $model->save();

        return $model;
    }

    private function stringValue(mixed $value): ?string
    {
        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }
}
