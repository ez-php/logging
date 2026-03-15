<?php

declare(strict_types=1);

namespace EzPhp\Logging;

/**
 * Interface LoggerInterface
 *
 * Contract for all logger implementations.
 *
 * @package EzPhp\Logging
 */
interface LoggerInterface
{
    /**
     * Log a message at an arbitrary level.
     *
     * @param string               $level   One of the LogLevel constants.
     * @param string               $message Human-readable description.
     * @param array<string, mixed> $context Optional structured context data.
     *
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void;

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function debug(string $message, array $context = []): void;

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function info(string $message, array $context = []): void;

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function warning(string $message, array $context = []): void;

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function error(string $message, array $context = []): void;

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function critical(string $message, array $context = []): void;
}
