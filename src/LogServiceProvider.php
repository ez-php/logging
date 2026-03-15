<?php

declare(strict_types=1);

namespace EzPhp\Logging;

use EzPhp\Application\Application;
use EzPhp\Config\Config;
use EzPhp\Exceptions\DefaultExceptionHandler;
use EzPhp\Exceptions\ExceptionHandler;
use EzPhp\ServiceProvider\ServiceProvider;

/**
 * Class LogServiceProvider
 *
 * Binds LoggerInterface to the driver configured via config/logging.php,
 * wires the static Log facade in boot(), and replaces the ExceptionHandler
 * binding with a LoggingExceptionHandler so that all unhandled exceptions
 * are automatically logged.
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
        $this->app->bind(LoggerInterface::class, function (Application $app): LoggerInterface {
            $config = $app->make(Config::class);
            $driver = $config->get('logging.driver');
            $driver = is_string($driver) ? $driver : 'file';

            return match ($driver) {
                'stdout' => new StdoutDriver(),
                'null' => new NullDriver(),
                default => new FileDriver($this->resolveLogPath($app, $config)),
            };
        });

        $this->app->bind(ExceptionHandler::class, function (Application $app): ExceptionHandler {
            $debug = (bool) $app->make(Config::class)->get('app.debug', false);

            return new LoggingExceptionHandler(
                new DefaultExceptionHandler($debug),
                $app->make(LoggerInterface::class),
            );
        });
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        Log::setLogger($this->app->make(LoggerInterface::class));
    }

    /**
     * @param Application $app
     * @param Config      $config
     *
     * @return string
     */
    private function resolveLogPath(Application $app, Config $config): string
    {
        $path = $config->get('logging.path');

        return is_string($path) && $path !== '' ? $path : $app->basePath('storage/logs');
    }
}
