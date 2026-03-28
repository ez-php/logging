<?php

declare(strict_types=1);

namespace EzPhp\Logging;

/**
 * Class JsonDriver
 *
 * Decorator that serialises each log entry as a JSON object and forwards the
 * resulting string to an inner logger as the message, with an empty context so
 * the inner driver does not double-encode.
 *
 * Output format:
 * {"timestamp":"2026-03-21T12:00:00+00:00","level":"info","message":"...","context":{...}}
 *
 * @package EzPhp\Logging
 */
final readonly class JsonDriver implements LoggerInterface
{
    /**
     * JsonDriver Constructor
     *
     * @param LoggerInterface $inner The inner logger that receives the serialised JSON line.
     */
    public function __construct(private LoggerInterface $inner)
    {
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
        $record = [
            'timestamp' => date('Y-m-d\TH:i:sP'),
            'level' => $level->value,
            'message' => $message,
            'context' => $context,
        ];

        $jsonLine = json_encode($record) ?: '{}';

        $this->inner->log($level, $jsonLine, []);
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
