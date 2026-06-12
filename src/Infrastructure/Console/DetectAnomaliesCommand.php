<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Throwable;
use Yammi\AuditLog\Application\DTO\AlertMessageData;
use Yammi\AuditLog\Application\DTO\AnomalyData;
use Yammi\AuditLog\Events\AnomalyDetected;
use Yammi\AuditLog\Infrastructure\Alert\AlertChannels;
use Yammi\AuditLog\Infrastructure\Alert\AlertLinker;
use Yammi\AuditLog\Infrastructure\Anomaly\AnomalyScanner;

/** @internal */
final class DetectAnomaliesCommand extends Command
{
    protected $signature = 'audit-log:detect-anomalies
                            {--window= : Look-back window in minutes (defaults to anomalies.window_minutes)}';

    protected $description = 'Scan recent audit records for suspicious patterns: change bursts, mass deletions, off-hours activity';

    public function handle(
        AnomalyScanner $scanner,
        ConfigRepository $config,
        Dispatcher $events,
        Mailer $mailer,
        AlertChannels $channels,
        AlertLinker $links,
    ): int {
        $windowOption = $this->option('window');
        $configured = $config->get('audit-log.anomalies.window_minutes', 60);
        $window = is_numeric($windowOption)
            ? max(1, (int) $windowOption)
            : max(1, is_numeric($configured) ? (int) $configured : 60);

        $findings = $scanner->scan($window);

        if ($findings === []) {
            $this->info("No anomalies detected in the last {$window} minute(s).");

            return self::SUCCESS;
        }

        $this->warn(count($findings)." anomaly(ies) detected in the last {$window} minute(s).");
        $this->table(
            ['Rule', 'Actor', 'Count', 'Details'],
            array_map(static fn (AnomalyData $finding): array => [
                $finding->rule,
                $finding->actorLabel.' ('.$finding->actorType.')',
                $finding->count,
                $finding->description,
            ], $findings),
        );

        foreach ($findings as $finding) {
            $events->dispatch(new AnomalyDetected($finding));
        }

        $this->mail($mailer, $this->recipients($config), $findings);

        $channels->dispatch(new AlertMessageData(
            kind: AlertMessageData::KIND_ANOMALY,
            title: count($findings).' audit '.(count($findings) === 1 ? 'anomaly' : 'anomalies').' detected',
            lines: array_map(
                static fn (AnomalyData $finding): string => "• [{$finding->rule}] {$finding->description}",
                $findings,
            ),
            occurredAt: $findings[0]->windowEnd,
            deepLink: $links->to('audit-log.anomalies'),
            context: ['window_minutes' => $window, 'findings' => count($findings)],
        ));

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $recipients
     * @param  list<AnomalyData>  $findings
     */
    private function mail(Mailer $mailer, array $recipients, array $findings): void
    {
        if ($recipients === []) {
            return;
        }

        $lines = array_map(
            static fn (AnomalyData $finding): string => "[{$finding->rule}] {$finding->description}",
            $findings,
        );

        try {
            $mailer->raw(
                "Audit anomalies detected.\n\n".implode("\n", $lines),
                static function (Message $message) use ($recipients, $findings): void {
                    $message->to($recipients)
                        ->subject('[Audit] '.count($findings).' anomaly(ies) detected');
                },
            );
        } catch (Throwable) {
        }
    }

    /**
     * @return list<string>
     */
    private function recipients(ConfigRepository $config): array
    {
        $configured = $config->get('audit-log.alerts.mail_to', []);

        if (! is_array($configured)) {
            return [];
        }

        $recipients = [];

        foreach ($configured as $recipient) {
            if (is_string($recipient) && $recipient !== '') {
                $recipients[] = $recipient;
            }
        }

        return $recipients;
    }
}
