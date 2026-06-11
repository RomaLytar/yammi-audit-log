<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Yammi\AuditLog\Application\Action\PruneAuditLogAction;

/** @internal */
final class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'retention_days' => [
                'required',
                'integer',
                'min:'.PruneAuditLogAction::MIN_DAYS,
                'max:'.PruneAuditLogAction::MAX_DAYS,
            ],
            'prune_schedule_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, bool|int|string>
     */
    public function settings(): array
    {
        return [
            'retention_days' => (int) $this->validated('retention_days'),
            'prune_schedule_enabled' => $this->boolean('prune_schedule_enabled'),
        ];
    }
}
