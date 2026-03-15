<?php

declare(strict_types=1);

namespace EzPhp\Logging;

use RuntimeException;

/**
 * Class Log
 *
 * Static facade for the LoggerInterface singleton.
 * Must be wired by LogServiceProvider::boot() before first use.
 * Call Log::resetLogger() in test tearDown() to prevent state leakage.
 *
 * @package EzPhp\Logging
 */
final class Log
{
    private static ?LoggerInterface $logger = null;

    /**
     * Wire the facade to a logger instance. Called by LogServiceProvider.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public static function setLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    /**
     * Reset the logger to null. Use in test tearDown() to prevent state leakage.
     *
     * @return void
     */
    public static function resetLogger(): void
    {
        self::$logger = null;
    }

    /**
     * @param string               $level
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        self::getLogger()->log($level, $message, $context);
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        self::getLogger()->debug($message, $context);
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::getLogger()->info($message, $context);
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        self::getLogger()->warning($message, $context);
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        self::getLogger()->error($message, $context);
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public static function critical(string $message, array $context = []): void
    {
        self::getLogger()->critical($message, $context);
    }

    /**
     * @return LoggerInterface
     */
    private static function getLogger(): LoggerInterface
    {
        if (self::$logger === null) {
            throw new RuntimeException(
                'Logger has not been initialized. Register LogServiceProvider before using the Log facade.'
            );
        }

        return self::$logger;
    }
}
