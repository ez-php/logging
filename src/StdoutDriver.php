<?php

declare(strict_types=1);

namespace EzPhp\Logging;

/**
 * Class StdoutDriver
 *
 * Writes log entries to stdout. Error and critical levels are written to stderr instead.
 * Line format: [YYYY-MM-DD HH:MM:SS] LEVEL: message {json_context}
 *
 * @package EzPhp\Logging
 */
final class StdoutDriver implements LoggerInterface
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
        $line = $this->formatLine($level, $message, $context);

        if (in_array($level, [LogLevel::ERROR, LogLevel::CRITICAL], true)) {
            fwrite(STDERR, $line);
        } else {
            echo $line;
        }
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

    /**
     * @param string               $level
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return string
     */
    private function formatLine(string $level, string $message, array $context): string
    {
        $datetime = date('Y-m-d H:i:s');
        $upper = strtoupper($level);
        $ctx = $context !== [] ? ' ' . (json_encode($context) ?: '{}') : '';

        return "[$datetime] $upper: $message$ctx\n";
    }
}
