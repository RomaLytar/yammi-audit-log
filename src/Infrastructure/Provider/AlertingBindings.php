<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Provider;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;
use Yammi\AuditLog\Application\Contract\AnomalyRule;
use Yammi\AuditLog\Application\Contract\Clock;
use Yammi\AuditLog\Application\Contract\Stream\LogStreamDriver;
use Yammi\AuditLog\Application\Service\AlertRuleMatcher;
use Yammi\AuditLog\Infrastructure\Alert\AlertChannels;
use Yammi\AuditLog\Infrastructure\Alert\AlertDispatcher;
use Yammi\AuditLog\Infrastructure\Alert\AlertLinker;
use Yammi\AuditLog\Infrastructure\Alert\Channel\SlackAlertChannel;
use Yammi\AuditLog\Infrastructure\Alert\Channel\WebhookAlertChannel;
use Yammi\AuditLog\Infrastructure\Anomaly\AnomalyScanner;
use Yammi\AuditLog\Infrastructure\Persistence\Mapper\AuditRecordMapper;
use Yammi\AuditLog\Infrastructure\Stream\ChangeStreamer;
use Yammi\AuditLog\Infrastructure\Stream\Driver\DatadogLogsDriver;
use Yammi\AuditLog\Infrastructure\Stream\Driver\ElasticDriver;
use Yammi\AuditLog\Infrastructure\Stream\Driver\HttpStreamDriver;
use Yammi\AuditLog\Infrastructure\Stream\Driver\SplunkHecDriver;

/**
 * Outbound side: mail/Slack/webhook alert channels, the alert dispatcher, the
 * SIEM change streamer and the anomaly scanner.
 *
 * @internal
 */
final class AlertingBindings extends BindingRegistrar
{
    public function register(): void
    {
        $this->app->singleton(AlertChannels::class, function (): AlertChannels {
            $config = $this->config();
            $appName = $config->get('app.name');
            $source = is_string($appName) && $appName !== '' ? $appName : null;

            $channels = [];

            $slackUrl = $config->get('audit-log.alerts.slack_webhook_url');

            if (is_string($slackUrl) && trim($slackUrl) !== '') {
                $channels[] = new SlackAlertChannel($this->app->make(HttpFactory::class), trim($slackUrl), $source);
            }

            $webhookUrl = $config->get('audit-log.alerts.webhook.url');
            $secret = $config->get('audit-log.alerts.webhook.secret');

            if (is_string($webhookUrl) && trim($webhookUrl) !== '') {
                $channels[] = new WebhookAlertChannel(
                    $this->app->make(HttpFactory::class),
                    trim($webhookUrl),
                    is_string($secret) && $secret !== '' ? $secret : null,
                    $source,
                );
            }

            return new AlertChannels($this->app->make(LoggerInterface::class), $channels);
        });

        $this->app->singleton(AlertDispatcher::class, function (): AlertDispatcher {
            $config = $this->config();
            $rules = $config->get('audit-log.alerts.rules', []);

            return new AlertDispatcher(
                new AlertRuleMatcher,
                $this->app->make(EventDispatcher::class),
                $this->app->make(Mailer::class),
                is_array($rules) ? array_values(array_filter($rules, is_array(...))) : [],
                $this->stringList($config->get('audit-log.alerts.mail_to', [])),
                $this->app->make(AlertChannels::class),
                $this->app->make(AlertLinker::class),
            );
        });

        $this->app->singleton(ChangeStreamer::class, function (): ChangeStreamer {
            $config = $this->config();
            $queue = $config->get('audit-log.stream.queue');

            return new ChangeStreamer(
                (bool) $config->get('audit-log.stream.enabled', false) ? $this->makeStreamDriver($config) : null,
                $this->app->make(BusDispatcher::class),
                $this->app->make(LoggerInterface::class),
                is_string($queue) && $queue !== '' ? $queue : null,
            );
        });

        $this->app->singleton(AnomalyScanner::class, function (): AnomalyScanner {
            $config = $this->config();

            return new AnomalyScanner(
                $this->app->make(Clock::class),
                max(0, (int) $config->get('audit-log.anomalies.rate_threshold', 200)),
                max(0, (int) $config->get('audit-log.anomalies.delete_threshold', 25)),
                $this->hourRange($config->get('audit-log.anomalies.off_hours', [])),
                $this->anomalyRules($config->get('audit-log.anomalies.rules', [])),
                $this->app->make(AuditRecordMapper::class),
            );
        });
    }

    private function makeStreamDriver(ConfigRepository $config): ?LogStreamDriver
    {
        $endpoint = $config->get('audit-log.stream.endpoint');

        if (! is_string($endpoint) || trim($endpoint) === '') {
            return null;
        }

        $endpoint = trim($endpoint);
        $token = $config->get('audit-log.stream.token');
        $token = is_string($token) ? $token : '';
        $source = $config->get('audit-log.stream.source');
        $source = is_string($source) && $source !== '' ? $source : 'audit-log';
        $headers = $this->stringMap($config->get('audit-log.stream.headers', []));
        $driver = $config->get('audit-log.stream.driver');

        return match (is_string($driver) ? $driver : 'http') {
            'splunk' => new SplunkHecDriver($endpoint, $token, $source, $headers),
            'datadog' => new DatadogLogsDriver($endpoint, $token, $source, $headers),
            'elastic' => new ElasticDriver($endpoint, $token !== '' ? $token : null, $headers),
            default => new HttpStreamDriver($endpoint, $token !== '' ? $token : null, $headers),
        };
    }

    /**
     * @return list<AnomalyRule>
     */
    private function anomalyRules(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rules = [];

        foreach ($value as $class) {
            if (is_string($class) && is_subclass_of($class, AnomalyRule::class)) {
                $instance = $this->app->make($class);

                if ($instance instanceof AnomalyRule) {
                    $rules[] = $instance;
                }
            }
        }

        return $rules;
    }
}
