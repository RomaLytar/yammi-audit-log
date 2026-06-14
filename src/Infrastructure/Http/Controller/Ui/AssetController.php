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
        $path = self::path($asset);

        if ($type === null || ! is_file($path)) {
            return new Response('Not found.', Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $type);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setMaxAge(31536000);
        $response->setImmutable();
        $response->setPublic();

        return $response;
    }

    /**
     * @return list<string>
     */
    public static function filenames(): array
    {
        return array_keys(self::TYPES);
    }

    /**
     * A short content hash used to bust the immutable browser cache when an
     * asset changes (e.g. after a package upgrade). Empty when the file is
     * absent.
     */
    public static function version(string $asset): string
    {
        $path = self::path($asset);

        return is_file($path) ? substr((string) md5_file($path), 0, 8) : '';
    }

    private static function path(string $asset): string
    {
        return __DIR__.'/../../../../../resources/assets/'.$asset;
    }
}
