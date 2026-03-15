<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Application\Application;
use EzPhp\Exceptions\ExceptionHandler;
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
use ReflectionException;

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
final class LogServiceProviderTest extends DatabaseTestCase
{
    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Log::resetLogger();
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function test_logger_interface_is_bound(): void
    {
        $app = $this->makeApp();

        $this->assertInstanceOf(LoggerInterface::class, $app->make(LoggerInterface::class));
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function test_exception_handler_is_wrapped_with_logging_decorator(): void
    {
        $app = $this->makeApp();

        $this->assertInstanceOf(LoggingExceptionHandler::class, $app->make(ExceptionHandler::class));
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function test_log_facade_is_wired_after_boot(): void
    {
        $app = $this->makeApp();

        // Facade should be wired — calling it must not throw
        ob_start();
        Log::info('facade test');
        ob_end_clean();

        $this->assertInstanceOf(LoggerInterface::class, $app->make(LoggerInterface::class));
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function test_file_driver_is_selected_by_default(): void
    {
        $app = $this->makeApp();

        $this->assertInstanceOf(FileDriver::class, $app->make(LoggerInterface::class));
    }

    /**
     * @return void
     */
    public function test_stdout_driver_is_instantiatable(): void
    {
        $this->assertInstanceOf(StdoutDriver::class, new StdoutDriver());
    }

    /**
     * @return Application
     * @throws ReflectionException
     */
    private function makeApp(): Application
    {
        $app = new Application();
        $app->register(LogServiceProvider::class);
        $app->bootstrap();

        return $app;
    }
}
