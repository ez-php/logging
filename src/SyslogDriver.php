<?php

declare(strict_types=1);

namespace EzPhp\Logging;

use Closure;
use InvalidArgumentException;

/**
 * Class SyslogDriver
 *
 * Writes log entries to the system logger (syslog / journald) through PHP's
 * stdlib openlog()/syslog() — no extension, no network. The usual target on a VM
 * or container host that has no log shipper; remote collectors are
 * ez-php/log-transport's concern.
 *
 * Levels map onto syslog priorities: debug → LOG_DEBUG, info → LOG_INFO,
 * warning → LOG_WARNING, error → LOG_ERR, critical → LOG_CRIT. A non-empty context
 * is JSON-encoded and appended to the message.
 *
 * @package EzPhp\Logging
 */
final class SyslogDriver implements LoggerInterface
{
    /**
     * Accepted facility names → LOG_* constants.
     *
     * @var array<string, int>
     */
    private const array FACILITIES = [
        'user' => LOG_USER,
        'daemon' => LOG_DAEMON,
        'auth' => LOG_AUTH,
        'local0' => LOG_LOCAL0,
        'local1' => LOG_LOCAL1,
        'local2' => LOG_LOCAL2,
        'local3' => LOG_LOCAL3,
        'local4' => LOG_LOCAL4,
        'local5' => LOG_LOCAL5,
        'local6' => LOG_LOCAL6,
        'local7' => LOG_LOCAL7,
    ];

    private readonly int $facility;

    private bool $opened = false;

    /**
     * @var Closure(int, string): void
     */
    private readonly Closure $writer;

    /**
     * @param string                          $ident    Program name prefixed to every entry.
     * @param string                          $facility One of user, daemon, auth, local0 … local7.
     * @param (Closure(int, string): void)|null $writer   Receives (priority, line); null = PHP's syslog().
     *                                                    Injectable so tests need no system logger.
     *
     * @throws InvalidArgumentException When the facility name is unknown.
     */
    public function __construct(
        private readonly string $ident = 'ez-php',
        string $facility = 'user',
        ?Closure $writer = null,
    ) {
        if (!isset(self::FACILITIES[$facility])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown syslog facility "%s"; use one of: %s.',
                $facility,
                implode(', ', array_keys(self::FACILITIES)),
            ));
        }

        $this->facility = self::FACILITIES[$facility];
        $this->writer = $writer ?? function (int $priority, string $line): void {
            if (!$this->opened) {
                openlog($this->ident, LOG_PID | LOG_ODELAY, $this->facility);
                $this->opened = true;
            }

            syslog($priority, $line);
        };
    }

    /**
     * Map a level onto its syslog priority.
     *
     * @param LogLevel $level
     *
     * @return int
     */
    public static function priority(LogLevel $level): int
    {
        return match ($level) {
            LogLevel::DEBUG => LOG_DEBUG,
            LogLevel::INFO => LOG_INFO,
            LogLevel::WARNING => LOG_WARNING,
            LogLevel::ERROR => LOG_ERR,
            LogLevel::CRITICAL => LOG_CRIT,
        };
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
        $line = $context !== [] ? $message . ' ' . (json_encode($context) ?: '{}') : $message;

        ($this->writer)(self::priority($level), $line);
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
     * The LOG_* facility constant entries are written with.
     *
     * @return int
     */
    public function facility(): int
    {
        return $this->facility;
    }
}
