<?php

declare(strict_types=1);

namespace EzPhp\Logging;

/**
 * Class FileDriver
 *
 * Appends log entries to a daily-rotated file inside the given directory.
 * File naming: app-YYYY-MM-DD.log
 * Line format:  [YYYY-MM-DD HH:MM:SS] LEVEL: message {json_context}
 *
 * @package EzPhp\Logging
 */
final readonly class FileDriver implements LoggerInterface
{
    /**
     * FileDriver Constructor
     *
     * @param string $directory Absolute path to the log directory (created on demand).
     */
    public function __construct(private string $directory)
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
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0o755, true);
        }

        file_put_contents(
            $this->filePath(),
            $this->formatLine($level, $message, $context),
            FILE_APPEND | LOCK_EX,
        );
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
     * @return string
     */
    private function filePath(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . 'app-' . date('Y-m-d') . '.log';
    }

    /**
     * @param LogLevel             $level
     * @param string               $message
     * @param array<string, mixed> $context
     *
     * @return string
     */
    private function formatLine(LogLevel $level, string $message, array $context): string
    {
        $datetime = date('Y-m-d H:i:s');
        $upper = strtoupper($level->value);
        $ctx = $context !== [] ? ' ' . (json_encode($context) ?: '{}') : '';

        return "[$datetime] $upper: $message$ctx\n";
    }
}
