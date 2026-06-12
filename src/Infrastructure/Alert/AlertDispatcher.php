<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Alert;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Throwable;
use Yammi\AuditLog\Application\DTO\TimelineEntryData;
use Yammi\AuditLog\Application\Service\AlertRuleMatcher;
use Yammi\AuditLog\Domain\Audit\Entity\AuditRecord;
use Yammi\AuditLog\Events\SensitiveChangeRecorded;

/**
 * Fires SensitiveChangeRecorded (and mails the configured recipients) for
 * every recorded change matching an alert rule. Fail-soft: an alerting
 * problem must never break the write path it piggybacks on.
 *
 * @internal
 */
final class AlertDispatcher
{
    /**
     * @param  list<array<string, mixed>>  $rules
     * @param  list<string>  $recipients
     */
    public function __construct(
        private readonly AlertRuleMatcher $matcher,
        private readonly Dispatcher $events,
        private readonly Mailer $mailer,
        private readonly array $rules = [],
        private readonly array $recipients = [],
    ) {}

    public function inspect(AuditRecord $record): void
    {
        if ($this->rules === []) {
            return;
        }

        try {
            $entry = TimelineEntryData::fromRecord($record);

            foreach ($this->matcher->matching($this->rules, $entry) as $rule) {
                $this->events->dispatch(new SensitiveChangeRecorded($entry, $rule));
                $this->mail($entry);
            }
        } catch (Throwable) {
        }
    }

    private function mail(TimelineEntryData $entry): void
    {
        if ($this->recipients === []) {
            return;
        }

        $body = sprintf(
            "Sensitive change recorded.\n\nModel: %s #%s\nEvent: %s\nActor: %s (%s)\nWhen: %s\nFields: %s",
            $entry->auditableType,
            $entry->auditableId,
            $entry->event,
            $entry->actorLabel,
            $entry->actorType,
            $entry->occurredAt,
            implode(', ', array_keys($entry->changes)),
        );

        $this->mailer->raw($body, function (Message $message) use ($entry): void {
            $message->to($this->recipients)
                ->subject(sprintf('[Audit] %s %s on %s', ucfirst($entry->event), $entry->auditableId, class_basename($entry->auditableType)));
        });
    }
}
