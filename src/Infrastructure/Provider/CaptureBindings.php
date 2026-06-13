<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Provider;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Yammi\AuditLog\Application\Contract\Resolver\ActorResolver;
use Yammi\AuditLog\Application\Contract\Resolver\CorrelationResolver;
use Yammi\AuditLog\Application\Contract\Resolver\ReasonResolver;
use Yammi\AuditLog\Application\Contract\Resolver\RequestContextResolver;
use Yammi\AuditLog\Application\Contract\ValueRedactor;
use Yammi\AuditLog\Application\Pipeline\RecordChangePipeline;
use Yammi\AuditLog\Application\Pipeline\Stage\ComputeDiffStage;
use Yammi\AuditLog\Application\Pipeline\Stage\ResolveActorStage;
use Yammi\AuditLog\Application\Pipeline\Stage\ResolveLabelsStage;
use Yammi\AuditLog\Application\Pipeline\Stage\ResolveRequestContextStage;
use Yammi\AuditLog\Infrastructure\Actor\ActorContext;
use Yammi\AuditLog\Infrastructure\Actor\ActorResolverChain;
use Yammi\AuditLog\Infrastructure\Actor\Provider\AuthenticatedUserProvider;
use Yammi\AuditLog\Infrastructure\Actor\Provider\ConsoleActorProvider;
use Yammi\AuditLog\Infrastructure\Actor\Provider\ImpersonationAwareUserProvider;
use Yammi\AuditLog\Infrastructure\Actor\Provider\QueuedJobActorProvider;
use Yammi\AuditLog\Infrastructure\Actor\Provider\SchedulerActorProvider;
use Yammi\AuditLog\Infrastructure\Capture\AuditableGuard;
use Yammi\AuditLog\Infrastructure\Context\ChangeReasonContext;
use Yammi\AuditLog\Infrastructure\Context\ContextReasonResolver;
use Yammi\AuditLog\Infrastructure\Context\HttpRequestContextResolver;
use Yammi\AuditLog\Infrastructure\Context\NullRequestContextResolver;
use Yammi\AuditLog\Infrastructure\Context\RequestContextHolder;
use Yammi\AuditLog\Infrastructure\Correlation\ContextCorrelationResolver;
use Yammi\AuditLog\Infrastructure\Correlation\CorrelationContext;
use Yammi\AuditLog\Infrastructure\Redaction\ConfigValueRedactor;

/**
 * Capture side: the context holders, the actor resolution chain, redaction,
 * the auditable guard and the record-change pipeline.
 *
 * @internal
 */
final class CaptureBindings extends BindingRegistrar
{
    public function register(): void
    {
        $this->app->singleton(ActorContext::class);
        $this->app->singleton(CorrelationContext::class);
        $this->app->singleton(ChangeReasonContext::class);
        $this->app->singleton(RequestContextHolder::class);
        $this->app->singleton(CorrelationResolver::class, ContextCorrelationResolver::class);
        $this->app->singleton(ReasonResolver::class, ContextReasonResolver::class);

        $this->app->singleton(RequestContextResolver::class, function (): RequestContextResolver {
            if (! (bool) $this->config()->get('audit-log.capture.request_context', false)) {
                return new NullRequestContextResolver;
            }

            return new HttpRequestContextResolver($this->app->make(RequestContextHolder::class));
        });

        $this->app->singleton(AuthenticatedUserProvider::class, function (): AuthenticatedUserProvider {
            return new AuthenticatedUserProvider(
                $this->app->make(AuthFactory::class),
                $this->stringList($this->config()->get('audit-log.actor.guards', [])),
            );
        });

        $this->app->singleton(ImpersonationAwareUserProvider::class, function (): ImpersonationAwareUserProvider {
            return new ImpersonationAwareUserProvider(
                $this->app->make(AuthenticatedUserProvider::class),
                $this->app->make(RequestContextHolder::class),
                $this->app->make(AuthFactory::class),
                $this->stringList($this->config()->get('audit-log.actor.impersonation_keys', ['impersonated_by'])),
                $this->stringList($this->config()->get('audit-log.actor.guards', [])),
            );
        });

        $this->app->singleton(ActorResolver::class, function (): ActorResolver {
            return new ActorResolverChain([
                $this->app->make(QueuedJobActorProvider::class),
                $this->app->make(SchedulerActorProvider::class),
                $this->app->make(ConsoleActorProvider::class),
                $this->app->make(ImpersonationAwareUserProvider::class),
            ], $this->app->make(ActorContext::class));
        });

        $this->app->singleton(ValueRedactor::class, function (): ValueRedactor {
            $config = $this->config();

            return new ConfigValueRedactor(
                $this->stringList($config->get('audit-log.redaction.keys', [])),
                (string) $config->get('audit-log.redaction.placeholder', '[redacted]'),
            );
        });

        $this->app->singleton(AuditableGuard::class, function (): AuditableGuard {
            $mode = $this->config()->get('audit-log.capture.mode', AuditableGuard::MODE_ALL);

            return new AuditableGuard(
                $this->stringList($this->config()->get('audit-log.capture.exclude', [])),
                $mode === AuditableGuard::MODE_OPT_IN ? AuditableGuard::MODE_OPT_IN : AuditableGuard::MODE_ALL,
            );
        });

        $this->app->singleton(ComputeDiffStage::class, function (): ComputeDiffStage {
            return new ComputeDiffStage(
                $this->app->make(ValueRedactor::class),
                $this->stringList($this->config()->get('audit-log.capture.ignore_attributes', [])),
            );
        });

        $this->app->singleton(RecordChangePipeline::class, function (): RecordChangePipeline {
            return new RecordChangePipeline([
                $this->app->make(ComputeDiffStage::class),
                $this->app->make(ResolveActorStage::class),
                $this->app->make(ResolveLabelsStage::class),
                $this->app->make(ResolveRequestContextStage::class),
            ]);
        });
    }
}
