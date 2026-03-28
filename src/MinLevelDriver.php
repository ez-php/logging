<?php

declare(strict_types=1);

namespace EzPhp\Logging;

/**
 * Class MinLevelDriver
 *
 * Decorator that silently drops log entries below a configured minimum severity.
 * Entries at or above the minimum level are forwarded to the inner logger unchanged.
 *
 * @package EzPhp\Logging
 */
final readonly class MinLevelDriver implements LoggerInterface
{
    /**
     * MinLevelDriver Constructor
     *
     * @param LoggerInterface $inner    The inner logger to forward accepted entries to.
     * @param LogLevel        $minLevel Minimum severity level; entries below this are dropped.
     */
    public function __construct(
        private LoggerInterface $inner,
        private LogLevel $minLevel,
    ) {
    }

    /**
     * @param LogLevel             $level
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function log(LogLevel $level, string $message, array $context = []): void
    {
        if (!$level->isAtLeast($this->minLevel)) {
            return;
        }

        $this->inner->log($level, $message, $context);
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }
}
