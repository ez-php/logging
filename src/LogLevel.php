<?php

declare(strict_types=1);

namespace EzPhp\Logging;

/**
 * Class LogLevel
 *
 * Supported log severity levels, ordered from least to most severe.
 *
 * @package EzPhp\Logging
 */
final class LogLevel
{
    public const DEBUG = 'debug';

    public const INFO = 'info';

    public const WARNING = 'warning';

    public const ERROR = 'error';

    public const CRITICAL = 'critical';

    /**
     * Returns all levels in ascending severity order.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::DEBUG, self::INFO, self::WARNING, self::ERROR, self::CRITICAL];
    }
}
