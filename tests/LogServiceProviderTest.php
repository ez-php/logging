<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Application\Application;
use EzPhp\Contracts\ExceptionHandlerInterface;
use EzPhp\Logging\FileDriver;
use EzPhp\Logging\Log;
use EzPhp\Logging\LoggerInterface;
use EzPhp\Logging\LoggingExceptionHandler;
use EzPhp\Logging\LogLevel;
use EzPhp\Logging\LogServiceProvider;
use EzPhp\Logging\NullDriver;
use EzPhp\Logging\StdoutDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Class LogServiceProviderTest
 *
 * @package Tests
 */
#[CoversClass(LogServiceProvider::class)]
#[UsesClass(LogLevel::class)]
#[UsesClass(FileDriver::class)]
#[UsesClass(NullDriver::class)]
#[UsesClass(StdoutDriver::class)]
#[UsesClass(LoggingExceptionHandler::class)]
#[UsesClass(Log::class)]
final class LogServiceProviderTest extends ApplicationTestCase
{
    /**
     * @param Application $app
     *
     * @return void
     */
    protected function configureApplication(Application $app): void
    {
        $app->register(LogServiceProvider::class);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Log::resetLogger();
        parent::tearDown();
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function test_logger_interface_is_bound(): void
    {
        $this->assertInstanceOf(LoggerInterface::class, $this->app()->make(LoggerInterface::class));
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function test_exception_handler_is_wrapped_with_logging_decorator(): void
    {
        $this->assertInstanceOf(LoggingExceptionHandler::class, $this->app()->make(ExceptionHandlerInterface::class));
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function test_log_facade_is_wired_after_boot(): void
    {
        // Facade is wired by the provider's boot() — calling it must not throw
        Log::info('facade test');

        $this->assertInstanceOf(LoggerInterface::class, $this->app()->make(LoggerInterface::class));
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function test_file_driver_is_selected_by_default(): void
    {
        $this->assertInstanceOf(FileDriver::class, $this->app()->make(LoggerInterface::class));
    }

    /**
     * @return void
     */
    public function test_stdout_driver_is_instantiatable(): void
    {
        $this->assertInstanceOf(StdoutDriver::class, new StdoutDriver());
    }
}
