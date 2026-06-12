<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Presentation\ViewModel;

use Yammi\AuditLog\Application\DTO\AnomalyData;

/**
 * Presents the anomaly scan for the UI: badge styles per rule, the window
 * selector options and the human summary of the active rules.
 *
 * @internal
 */
final class AnomaliesViewModel
{
    private const BADGES = [
        AnomalyData::RULE_RATE_SPIKE => ['Change burst', 'warning', 'trending-up'],
        AnomalyData::RULE_MASS_DELETE => ['Mass delete', 'destructive', 'trash-2'],
        AnomalyData::RULE_OFF_HOURS => ['Off hours', 'info', 'moon'],
    ];

    /**
     * @param  list<AnomalyData>  $findings
     * @param  array<int, string>  $windows
     * @param  list<string>  $rules
     */
    public function __construct(
        private readonly array $findings,
        private readonly int $window,
        private readonly array $windows,
        private readonly array $rules,
        private readonly ?string $scanCron,
    ) {}

    public function isEmpty(): bool
    {
        return $this->findings === [];
    }

    public function count(): int
    {
        return count($this->findings);
    }

    /**
     * @return list<array{rule: string, tone: string, icon: string, actorType: string, actorLabel: string, count: int, description: string}>
     */
    public function rows(): array
    {
        $rows = [];

        foreach ($this->findings as $finding) {
            [$label, $tone, $icon] = self::BADGES[$finding->rule] ?? [$finding->rule, 'warning', 'alert-triangle'];

            $rows[] = [
                'rule' => $label,
                'tone' => $tone,
                'icon' => $icon,
                'actorType' => $finding->actorType,
                'actorLabel' => $finding->actorLabel,
                'count' => $finding->count,
                'description' => $finding->description,
            ];
        }

        return $rows;
    }

    public function window(): string
    {
        return (string) $this->window;
    }

    public function windowLabel(): string
    {
        return $this->windows[$this->window] ?? 'Last 24 hours';
    }

    /**
     * @return array<string, string>
     */
    public function windowOptions(): array
    {
        $options = [];

        foreach ($this->windows as $minutes => $label) {
            $options[(string) $minutes] = $label;
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    public function scanCron(): ?string
    {
        return $this->scanCron;
    }
}
