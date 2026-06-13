<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Http\Controller\Ui;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the dashboard's vendored static assets (compiled Tailwind CSS, the
 * lucide icon script and the Inter font files) straight from the package, so
 * the admin UI needs no external CDN and no vendor:publish step. The filename
 * is whitelisted, which also rules out path traversal.
 *
 * @internal
 */
final class AssetController
{
    private const TYPES = [
        'dashboard.css' => 'text/css',
        'lucide.js' => 'text/javascript',
        'inter-latin.woff2' => 'font/woff2',
        'inter-latin-ext.woff2' => 'font/woff2',
    ];

    public function __invoke(string $asset): Response
    {
        $type = self::TYPES[$asset] ?? null;
        $path = __DIR__.'/../../../../../resources/assets/'.$asset;

        if ($type === null || ! is_file($path)) {
            return new Response('Not found.', Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $type);
        $response->setMaxAge(31536000);
        $response->setImmutable();
        $response->setPublic();

        return $response;
    }
}
