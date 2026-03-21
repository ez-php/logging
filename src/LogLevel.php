<?php

declare(strict_types=1);

namespace EzPhp\Logging;

/**
 * Enum LogLevel
 *
 * Supported log severity levels, ordered from least to most severe.
 *
 * @package EzPhp\Logging
 */
enum LogLevel: string
{
    case DEBUG = 'debug';

    case INFO = 'info';

    case WARNING = 'warning';

    case ERROR = 'error';

    case CRITICAL = 'critical';

    /**
     * Returns all levels in ascending severity order.
     *
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
