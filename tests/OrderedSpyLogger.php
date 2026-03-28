<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Logging\LoggerInterface;
use EzPhp\Logging\LogLevel;

/**
 * Class OrderedSpyLogger
 *
 * A logger that records its name into an OrderTracker on each log() call.
 * Used in StackDriverTest to verify that drivers are invoked in order.
 *
 * @package Tests
 */
final class OrderedSpyLogger implements LoggerInterface
{
    /**
     * OrderedSpyLogger Constructor
     *
     * @param string       $name    Label recorded to the tracker on each log call.
     * @param OrderTracker $tracker Shared tracker that accumulates call labels.
     */
    public function __construct(
        private readonly string $name,
        private readonly OrderTracker $tracker,
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
        $this->tracker->record($this->name);
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
