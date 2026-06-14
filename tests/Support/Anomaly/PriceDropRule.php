<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Support\Anomaly;

use DateTimeInterface;
use Yammi\AuditLog\Application\Contract\AnomalyRule;
use Yammi\AuditLog\Application\DTO\Anomaly\AnomalyData;
use Yammi\AuditLog\Application\DTO\Anomaly\AnomalyWindow;

final class PriceDropRule implements AnomalyRule
{
    public function key(): string
    {
        return 'price_drop';
    }

    public function evaluate(array $entries, AnomalyWindow $window): array
    {
        $findings = [];

        foreach ($entries as $entry) {
            if ($entry->event !== 'updated') {
                continue;
            }

            $price = $entry->changes['price'] ?? null;

            if (! is_array($price)) {
                continue;
            }

            $old = $price['old'] ?? null;
            $new = $price['new'] ?? null;

            if (is_numeric($old) && is_numeric($new) && (float) $new < (float) $old) {
                $findings[] = new AnomalyData(
                    rule: $this->key(),
                    actorType: $entry->actorType,
                    actorLabel: $entry->actorLabel,
                    count: 1,
                    windowStart: $window->start->format(DateTimeInterface::ATOM),
                    windowEnd: $window->end->format(DateTimeInterface::ATOM),
                    description: sprintf('Price dropped from %s to %s on %s #%s.', $old, $new, $entry->model(), $entry->auditableId),
                    severity: AnomalyData::SEVERITY_HIGH,
                );
            }
        }

        return $findings;
    }
}
