<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Provider;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Router;
use Psr\Log\LoggerInterface;
use Throwable;
use Yammi\AuditLog\Application\Contract\Query\AuditLogQuery;
use Yammi\AuditLog\Infrastructure\Http\AuthGuardDetector;
use Yammi\AuditLog\Infrastructure\Http\Controller\Ui\AssetController;
use Yammi\AuditLog\Infrastructure\Http\Controller\Ui\ScopedActivityController;

/**
 * Registers the dashboard routes, the signed per-subject activity route, the
 * nav view composer and the opt-in JSON API (fail-closed without an auth guard).
 *
 * @internal
 */
final class HttpRegistrar
{
    public function __construct(
        private readonly Application $app,
        private readonly string $webRoutes,
        private readonly string $apiRoutes,
    ) {}

    public function register(ConfigRepository $config): void
    {
        if ((bool) $config->get('audit-log.ui.enabled', true)) {
            $this->registerAssetRoute($config);
            $this->registerRoutes($config);
            $this->registerActivityRoute($config);
            $this->registerNavComposer();
        }

        if ((bool) $config->get('audit-log.api.enabled', false)) {
            $this->registerApiRoutes($config);
        }
    }

    private function registerRoutes(ConfigRepository $config): void
    {
        $path = $config->get('audit-log.ui.path', 'audit-log');
        $configured = $config->get('audit-log.ui.middleware', ['web', 'auth']);
        $middleware = is_array($configured) ? array_values($configured) : ['web', 'auth'];

        $throttle = $config->get('audit-log.ui.throttle');
        if (is_string($throttle) && $throttle !== '') {
            $middleware[] = 'throttle:'.$throttle;
        }

        $gate = $config->get('audit-log.ui.gate');
        if (is_string($gate) && $gate !== '') {
            $middleware[] = 'can:'.$gate;
        }

        $this->app->make(Router::class)->group([
            'prefix' => is_string($path) ? $path : 'audit-log',
            'middleware' => $middleware,
        ], function (): void {
            $this->loadRoutes($this->webRoutes);
        });
    }

    private function registerAssetRoute(ConfigRepository $config): void
    {
        $path = $config->get('audit-log.ui.path', 'audit-log');
        $router = $this->app->make(Router::class);

        $router->group([
            'prefix' => is_string($path) ? $path : 'audit-log',
        ], static function () use ($router): void {
            $router->get('assets/{asset}', AssetController::class)
                ->where('asset', '[A-Za-z0-9._-]+')
                ->name('audit-log.asset');
        });
    }

    private function registerActivityRoute(ConfigRepository $config): void
    {
        $path = $config->get('audit-log.ui.path', 'audit-log');
        $router = $this->app->make(Router::class);

        $router->group([
            'prefix' => is_string($path) ? $path : 'audit-log',
            'middleware' => ['signed'],
        ], static function () use ($router): void {
            $router->get('activity', ScopedActivityController::class)->name('audit-log.activity');
        });
    }

    private function registerApiRoutes(ConfigRepository $config): void
    {
        $path = $config->get('audit-log.api.path', 'audit-log/api');
        $configured = $config->get('audit-log.api.middleware', ['api']);
        $middleware = is_array($configured) ? array_values($configured) : ['api'];

        if (! (bool) $config->get('audit-log.api.allow_unauthenticated', false)
            && ! (new AuthGuardDetector)->hasGuard($middleware)) {
            $this->app->make(LoggerInterface::class)->error(
                'audit-log API routes were not registered: audit-log.api.middleware carries no authentication guard. '
                .'Add an auth guard (e.g. "auth:sanctum"), or set audit-log.api.allow_unauthenticated=true to override.',
            );

            return;
        }

        $this->app->make(Router::class)->group([
            'prefix' => is_string($path) ? $path : 'audit-log/api',
            'middleware' => $middleware,
        ], function (): void {
            $this->loadRoutes($this->apiRoutes);
        });
    }

    private function registerNavComposer(): void
    {
        $this->app->make(ViewFactory::class)->composer(
            'audit-log::layouts.app',
            function (View $view): void {
                try {
                    $view->with('auditNoiseCount', $this->app->make(AuditLogQuery::class)->countNoise());
                } catch (Throwable) {
                    $view->with('auditNoiseCount', 0);
                }
            },
        );
    }

    private function loadRoutes(string $path): void
    {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        require $path;
    }
}
