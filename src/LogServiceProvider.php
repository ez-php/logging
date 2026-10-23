<?php

declare(strict_types=1);

namespace EzPhp\Logging;

use EzPhp\Contracts\ConfigInterface;
use EzPhp\Contracts\ContainerInterface;
use EzPhp\Contracts\ExceptionHandlerInterface;
use EzPhp\Contracts\ServiceProvider;

/**
 * Class LogServiceProvider
 *
 * Binds LoggerInterface to the driver configured via config/logging.php,
 * wires the static Log facade in boot(), and wraps the registered
 * ExceptionHandlerInterface binding with a LoggingExceptionHandler so that
 * all unhandled exceptions are automatically logged.
 *
 * Supported drivers: file, stdout, syslog, null, json, stack.
 * Optional wrappers: MinLevelDriver (when logging.min_level is set).
 *
 * @package EzPhp\Logging
 */
final class LogServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(LoggerInterface::class, function (ContainerInterface $app): LoggerInterface {
            $config = $app->make(ConfigInterface::class);
            $driver = $config->get('logging.driver');
            $driver = is_string($driver) ? $driver : 'file';

            $logger = $this->makeDriver($driver, $config);

            $minLevel = $config->get('logging.min_level');

            if (is_string($minLevel) && $minLevel !== '') {
                try {
                    $logger = new MinLevelDriver($logger, LogLevel::from($minLevel));
                } catch (\ValueError) {
                    // invalid level string — skip wrapping
                }
            }

            return $logger;
        });
    }

    /**
     * Wraps the framework-registered ExceptionHandlerInterface with a logging
     * decorator and wires the static Log facade.
     *
     * Runs in boot() — after all register() calls — so the inner handler is
     * already bound and can be resolved without circular reference.
     *
     * @return void
     */
    public function boot(): void
    {
        $inner = $this->app->make(ExceptionHandlerInterface::class);
        $logger = $this->app->make(LoggerInterface::class);

        $this->app->instance(ExceptionHandlerInterface::class, new LoggingExceptionHandler($inner, $logger));

        Log::setLogger($logger);
    }

    /**
     * Build a logger driver by name using the given config.
     *
     * @param string          $driver
     * @param ConfigInterface $config
     *
     * @return LoggerInterface
     */
    private function makeDriver(string $driver, ConfigInterface $config): LoggerInterface
    {
        return match ($driver) {
            'stdout' => new StdoutDriver(),
            'null' => new NullDriver(),
            'syslog' => $this->makeSyslogDriver($config),
            'json' => $this->makeJsonDriver($config),
            'stack' => $this->makeStackDriver($config),
            default => new FileDriver(
                $this->resolveLogPath($config),
                $this->resolveMaxBytes($config),
            ),
        };
    }

    /**
     * Build a named sub-driver for use inside a json or stack driver.
     * Only file, stdout, syslog and null are supported as sub-drivers.
     *
     * @param string          $name
     * @param ConfigInterface $config
     *
     * @return LoggerInterface
     */
    private function makeSubDriver(string $name, ConfigInterface $config): LoggerInterface
    {
        return match ($name) {
            'stdout' => new StdoutDriver(),
            'null' => new NullDriver(),
            'syslog' => $this->makeSyslogDriver($config),
            default => new FileDriver(
                $this->resolveLogPath($config),
                $this->resolveMaxBytes($config),
            ),
        };
    }

    /**
     * Build the syslog driver from logging.syslog.ident / logging.syslog.facility.
     *
     * @param ConfigInterface $config
     *
     * @return SyslogDriver
     */
    private function makeSyslogDriver(ConfigInterface $config): SyslogDriver
    {
        $ident = $config->get('logging.syslog.ident');
        $facility = $config->get('logging.syslog.facility');

        return new SyslogDriver(
            is_string($ident) && $ident !== '' ? $ident : 'ez-php',
            is_string($facility) && $facility !== '' ? $facility : 'user',
        );
    }

    /**
     * Build the JSON driver, wrapping a configured inner driver.
     *
     * @param ConfigInterface $config
     *
     * @return JsonDriver
     */
    private function makeJsonDriver(ConfigInterface $config): JsonDriver
    {
        $innerName = $config->get('logging.json_inner');
        $inner = is_string($innerName) ? $this->makeSubDriver($innerName, $config) : new StdoutDriver();

        return new JsonDriver($inner);
    }

    /**
     * Build the stack driver from the list of driver names in logging.stack.
     * Unknown entries are silently skipped.
     *
     * @param ConfigInterface $config
     *
     * @return StackDriver
     */
    private function makeStackDriver(ConfigInterface $config): StackDriver
    {
        $stack = $config->get('logging.stack');
        $stack = is_array($stack) ? $stack : [];

        $drivers = [];

        foreach ($stack as $name) {
            if (!is_string($name)) {
                continue;
            }

            if (!in_array($name, ['file', 'stdout', 'syslog', 'null'], true)) {
                continue;
            }

            $drivers[] = $this->makeSubDriver($name, $config);
        }

        return new StackDriver($drivers);
    }

    /**
     * @param ConfigInterface $config
     *
     * @return string
     */
    private function resolveLogPath(ConfigInterface $config): string
    {
        $path = $config->get('logging.path');

        return is_string($path) && $path !== '' ? $path : sys_get_temp_dir() . '/ez-php-logs';
    }

    /**
     * @param ConfigInterface $config
     *
     * @return int
     */
    private function resolveMaxBytes(ConfigInterface $config): int
    {
        $maxBytes = $config->get('logging.max_bytes');

        return is_int($maxBytes) ? $maxBytes : 0;
    }
}
