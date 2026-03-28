<?php

declare(strict_types=1);

namespace EzPhp\Logging;

/**
 * Class ContextualLogger
 *
 * Decorator that merges a fixed context array into every log call.
 * Used by RequestContextMiddleware to attach request-scoped data
 * (request ID, IP, method, path, user ID) to all log entries produced
 * during a single request.
 *
 * @package EzPhp\Logging
 */
final readonly class ContextualLogger implements LoggerInterface
{
    /**
     * ContextualLogger Constructor
     *
     * @param LoggerInterface      $inner   The inner logger to delegate to.
     * @param array<string, mixed> $context Fixed context merged into every log entry.
     */
    public function __construct(
        private LoggerInterface $inner,
        private array $context,
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
        $this->inner->log($level, $message, array_merge($this->context, $context));
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
