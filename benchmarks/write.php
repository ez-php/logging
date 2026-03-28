<?php

declare(strict_types=1);

/**
 * Performance benchmark for EzPhp\Logging\Log (NullDriver).
 *
 * Measures the overhead of the logger pipeline — level filtering,
 * context formatting, and driver dispatch — using the NullDriver
 * so no I/O is performed and only pure CPU cost is measured.
 *
 * Exits with code 1 if the per-log time exceeds the defined threshold,
 * allowing CI to detect performance regressions automatically.
 *
 * Usage:
 *   php benchmarks/write.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use EzPhp\Logging\Log;
use EzPhp\Logging\NullDriver;

const ITERATIONS = 10000;
const LOGS_PER_ITER = 5;
const THRESHOLD_MS = 0.5; // per-iteration upper bound in milliseconds

// ── Setup ─────────────────────────────────────────────────────────────────────

Log::setLogger(new NullDriver());

// Warm-up
Log::info('warm-up', ['key' => 'value']);

// ── Benchmark ─────────────────────────────────────────────────────────────────

$start = hrtime(true);

for ($i = 0; $i < ITERATIONS; $i++) {
    Log::debug('Debug message at iteration :i', ['i' => $i]);
    Log::info('User logged in', ['user_id' => 42, 'ip' => '127.0.0.1']);
    Log::warning('Slow query detected', ['query_time_ms' => 250]);
    Log::error('Failed to send email', ['to' => 'user@example.com', 'error' => 'timeout']);
    Log::critical('Service unavailable', ['service' => 'payment', 'attempt' => $i]);
}

$end = hrtime(true);

Log::resetLogger();

$totalMs = ($end - $start) / 1_000_000;
$perIter = $totalMs / ITERATIONS;

echo sprintf(
    "Logger Benchmark (NullDriver)\n" .
    "  Log calls per iter   : %d (debug/info/warning/error/critical)\n" .
    "  Iterations           : %d\n" .
    "  Total time           : %.2f ms\n" .
    "  Per iteration        : %.3f ms\n" .
    "  Threshold            : %.1f ms\n",
    LOGS_PER_ITER,
    ITERATIONS,
    $totalMs,
    $perIter,
    THRESHOLD_MS,
);

if ($perIter > THRESHOLD_MS) {
    echo sprintf(
        "FAIL: %.3f ms exceeds threshold of %.1f ms\n",
        $perIter,
        THRESHOLD_MS,
    );
    exit(1);
}

echo "PASS\n";
exit(0);
