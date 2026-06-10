<?php

declare(strict_types=1);

$file = $argv[1] ?? 'coverage.clover';
$minimum = (float) ($argv[2] ?? 90);

if (! is_file($file)) {
    fwrite(STDERR, "Coverage report not found: {$file}\n");
    exit(1);
}

$xml = simplexml_load_file($file);

if ($xml === false || ! isset($xml->project->metrics)) {
    fwrite(STDERR, "Cannot parse coverage report: {$file}\n");
    exit(1);
}

$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$coverage = $statements === 0 ? 100.0 : $covered / $statements * 100;

printf("Line coverage: %.2f%% (%d/%d statements), minimum required: %.2f%%\n", $coverage, $covered, $statements, $minimum);

if ($coverage < $minimum) {
    fwrite(STDERR, "Coverage is below the minimum.\n");
    exit(1);
}
