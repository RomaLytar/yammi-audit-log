<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Persistence\Repository;

use DateTimeImmutable;
use Throwable;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Domain\Audit\Repository\AuditRecordRepository;
use Yammi\AuditLog\Domain\Audit\ValueObject\AuditableReference;
use Yammi\AuditLog\Domain\Settings\Repository\GeneralSettingRepository;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditChangedKeyModel;
use Yammi\AuditLog\Infrastructure\Persistence\Eloquent\AuditRecordModel;
use Yammi\AuditLog\Infrastructure\Persistence\Mapper\AuditRecordMapper;

/** @internal */
final class EloquentAuditRecordRepository implements AuditRecordRepository
{
    private const PRUNE_CHUNK = 1000;

    public function __construct(
        private readonly AuditRecordMapper $mapper,
        private readonly AuditRowWriter $writer,
        private readonly GeneralSettingRepository $settings,
        private readonly int $pruneChunkSize = self::PRUNE_CHUNK,
    ) {}

    public function save(AuditRecord $record): void
    {
        $this->writer->insert($this->mapper->toRow($record)->toArray());
    }

    public function timelineFor(AuditableReference $auditable, int $limit = 50): array
    {
        $models = AuditRecordModel::query()
            ->where('auditable_type', $auditable->type)
            ->where('auditable_id', $auditable->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $records = [];

        foreach ($models as $model) {
            $records[] = $this->mapper->toDomain($model);
        }

        return $records;
    }

    public function deleteOlderThan(DateTimeImmutable $cutoff): int
    {
        $total = 0;
        $anchor = null;

        do {
            $ids = AuditRecordModel::query()
                ->withoutGlobalScopes()
                ->where('occurred_at', '<', $cutoff->format('Y-m-d H:i:s'))
                ->orderBy('id')
                ->limit($this->pruneChunkSize)
                ->pluck('id')
                ->all();

            if ($ids === []) {
                break;
            }

            $newestHash = AuditRecordModel::query()
                ->withoutGlobalScopes()
                ->whereIn('id', $ids)
                ->orderByDesc('id')
                ->value('integrity_hash');

            if (is_string($newestHash) && $newestHash !== '') {
                $anchor = $newestHash;
            }

            AuditChangedKeyModel::query()->whereIn('audit_id', $ids)->delete();

            $total += AuditRecordModel::query()->withoutGlobalScopes()->whereIn('id', $ids)->delete();
        } while (count($ids) === $this->pruneChunkSize);

        if ($anchor !== null) {
            $this->storeChainAnchor($anchor);
        }

        return $total;
    }

    /**
     * Pruned rows leave the chain headless; remembering the newest pruned hash
     * keeps audit-log:verify strict instead of trusting the first survivor.
     */
    private function storeChainAnchor(string $anchor): void
    {
        try {
            $this->settings->set('integrity', 'chain_anchor', $anchor, 'string');
        } catch (Throwable) {
        }
    }
}
