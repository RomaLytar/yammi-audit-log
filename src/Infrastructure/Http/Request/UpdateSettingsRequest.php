<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Yammi\AuditLog\Application\Action\Retention\PruneAuditLogAction;

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
            'enabled' => ['sometimes', 'boolean'],
            'retention_days' => [
                'required',
                'integer',
                'min:'.PruneAuditLogAction::MIN_DAYS,
                'max:'.PruneAuditLogAction::MAX_DAYS,
            ],
            'prune_schedule_enabled' => ['sometimes', 'boolean'],
            'prune_cron' => ['required', 'string', 'max:100', 'regex:/^[\d\*\/\-,\s]+$/'],
            'write_async' => ['sometimes', 'boolean'],
            'write_queue' => ['nullable', 'string', 'max:100'],
            'integrity_enabled' => ['sometimes', 'boolean'],
            'ignore_attributes' => ['nullable', 'string', 'max:500'],
            'request_context' => ['sometimes', 'boolean'],
            'redaction_keys' => ['nullable', 'string', 'max:1000'],
            'redaction_placeholder' => ['required', 'string', 'max:50'],
            'timezone' => ['nullable', 'string', 'timezone:all'],
            'ui_enabled' => ['sometimes', 'boolean'],
            'ui_throttle' => ['nullable', 'string', 'max:20', 'regex:/^\d+,\d+$/'],
            'jobs_monitor_url' => ['nullable', 'string', 'max:255'],
            'observability_trace_url' => ['nullable', 'string', 'max:500'],
            'observability_postman' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, bool|int|string>
     */
    public function settings(): array
    {
        return [
            'enabled' => $this->boolean('enabled'),
            'retention_days' => (int) $this->validated('retention_days'),
            'prune_schedule_enabled' => $this->boolean('prune_schedule_enabled'),
            'prune_cron' => $this->stringValue('prune_cron', '0 3 * * *'),
            'write_async' => $this->boolean('write_async'),
            'write_queue' => $this->stringValue('write_queue'),
            'integrity_enabled' => $this->boolean('integrity_enabled'),
            'ignore_attributes' => $this->stringValue('ignore_attributes'),
            'request_context' => $this->boolean('request_context'),
            'redaction_keys' => $this->stringValue('redaction_keys'),
            'redaction_placeholder' => $this->stringValue('redaction_placeholder', '[redacted]'),
            'timezone' => $this->stringValue('timezone'),
            'ui_enabled' => $this->boolean('ui_enabled'),
            'ui_throttle' => $this->stringValue('ui_throttle'),
            'jobs_monitor_url' => $this->stringValue('jobs_monitor_url'),
            'observability_trace_url' => $this->stringValue('observability_trace_url'),
            'observability_postman' => $this->boolean('observability_postman'),
        ];
    }

    private function stringValue(string $key, string $default = ''): string
    {
        $value = $this->validated($key);

        return is_string($value) ? trim($value) : $default;
    }
}
