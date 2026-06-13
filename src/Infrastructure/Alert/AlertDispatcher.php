<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Alert;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Throwable;
use Yammi\AuditLog\Application\DTO\Alert\AlertMessageData;
use Yammi\AuditLog\Application\DTO\Audit\TimelineEntryData;
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
        private readonly ?AlertChannels $channels = null,
        private readonly ?AlertLinker $links = null,
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
                $this->channels?->dispatch($this->message($entry));
            }
        } catch (Throwable) {
        }
    }

    private function message(TimelineEntryData $entry): AlertMessageData
    {
        return new AlertMessageData(
            kind: AlertMessageData::KIND_SENSITIVE_CHANGE,
            title: sprintf('%s %s #%s', ucfirst($entry->event), $entry->model(), $entry->auditableId),
            lines: [
                "*Model:* {$entry->auditableType} #{$entry->auditableId}",
                "*Event:* {$entry->event}",
                "*Actor:* {$entry->actorLabel} ({$entry->actorType})",
                '*Fields:* '.implode(', ', array_keys($entry->changes)),
            ],
            occurredAt: $entry->occurredAt,
            deepLink: $this->links?->to('audit-log.dashboard', [
                'type' => $entry->auditableType,
                'id' => $entry->auditableId,
            ]),
            context: [
                'model' => $entry->auditableType,
                'id' => $entry->auditableId,
                'event' => $entry->event,
                'actor' => $entry->actorLabel,
                'actor_type' => $entry->actorType,
            ],
        );
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
