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

            return match ($driver) {
                'stdout' => new StdoutDriver(),
                'null' => new NullDriver(),
                default => new FileDriver($this->resolveLogPath($config)),
            };
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
     * @param ConfigInterface $config
     *
     * @return string
     */
    private function resolveLogPath(ConfigInterface $config): string
    {
        $path = $config->get('logging.path');

        return is_string($path) && $path !== '' ? $path : sys_get_temp_dir() . '/ez-php-logs';
    }
}
