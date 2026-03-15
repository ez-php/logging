<?php

declare(strict_types=1);

namespace EzPhp\Logging;

/**
 * Class NullDriver
 *
 * Discards all log entries silently. Useful in tests or when logging is intentionally disabled.
 *
 * @package EzPhp\Logging
 */
final class NullDriver implements LoggerInterface
{
    /**
     * @param string               $level
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void
    {
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
    }
}
