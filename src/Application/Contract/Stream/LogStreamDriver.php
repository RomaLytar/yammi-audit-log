<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Application\Contract\Stream;

/**
 * A SIEM/log destination: where to POST audit events, with what headers, and
 * how to wrap the normalized event in that system's expected envelope. The
 * common event shape is built once by the streamer; drivers only differ in
 * transport metadata and envelope (Splunk HEC, Datadog, Elastic, plain HTTP).
 */
interface LogStreamDriver
{
    public function name(): string;

    public function endpoint(): string;

    /**
     * @return array<string, string>
     */
    public function headers(): array;

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    public function envelope(array $event): array;
}
