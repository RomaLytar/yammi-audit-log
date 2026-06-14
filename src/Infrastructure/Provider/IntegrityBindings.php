<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Infrastructure\Provider;

use Yammi\AuditLog\Infrastructure\Integrity\IntegrityHasher;
use Yammi\AuditLog\Infrastructure\Integrity\IntegritySigner;
use Yammi\AuditLog\Infrastructure\Persistence\Repository\AuditRowWriter;

/**
 * Hash-chain integrity: the single insert writer and the digest signer.
 *
 * @internal
 */
final class IntegrityBindings extends BindingRegistrar
{
    public function register(): void
    {
        $this->app->singleton(AuditRowWriter::class, function (): AuditRowWriter {
            return new AuditRowWriter(
                new IntegrityHasher,
                (bool) $this->config()->get('audit-log.integrity.enabled', false),
            );
        });

        $this->app->singleton(IntegritySigner::class, function (): IntegritySigner {
            $config = $this->config();
            $private = $config->get('audit-log.integrity.signing.private_key');
            $public = $config->get('audit-log.integrity.signing.public_key');

            return new IntegritySigner(
                is_string($private) && $private !== '' ? $private : null,
                is_string($public) && $public !== '' ? $public : null,
            );
        });
    }
}
