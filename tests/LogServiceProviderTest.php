<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Application\Application;
use EzPhp\Application\CoreServiceProviders;
use EzPhp\Config\Config;
use EzPhp\Config\ConfigLoader;
use EzPhp\Config\ConfigServiceProvider;
use EzPhp\Console\Command\MakeControllerCommand;
use EzPhp\Console\Command\MakeMiddlewareCommand;
use EzPhp\Console\Command\MakeMigrationCommand;
use EzPhp\Console\Command\MakeProviderCommand;
use EzPhp\Console\Command\MigrateCommand;
use EzPhp\Console\Command\MigrateRollbackCommand;
use EzPhp\Console\Console;
use EzPhp\Console\ConsoleServiceProvider;
use EzPhp\Console\Input;
use EzPhp\Console\Output;
use EzPhp\Container\Container;
use EzPhp\Database\Database;
use EzPhp\Database\DatabaseServiceProvider;
use EzPhp\Exceptions\DefaultExceptionHandler;
use EzPhp\Exceptions\ExceptionHandler;
use EzPhp\Exceptions\ExceptionHandlerServiceProvider;
use EzPhp\Logging\FileDriver;
use EzPhp\Logging\Log;
use EzPhp\Logging\LoggerInterface;
use EzPhp\Logging\LoggingExceptionHandler;
use EzPhp\Logging\LogLevel;
use EzPhp\Logging\LogServiceProvider;
use EzPhp\Logging\NullDriver;
use EzPhp\Logging\StdoutDriver;
use EzPhp\Migration\MigrationServiceProvider;
use EzPhp\Migration\Migrator;
use EzPhp\Routing\Route;
use EzPhp\Routing\Router;
use EzPhp\Routing\RouterServiceProvider;
use EzPhp\ServiceProvider\ServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionException;

/**
 * Class LogServiceProviderTest
 *
 * @package Tests
 */
#[CoversClass(LogServiceProvider::class)]
#[UsesClass(Application::class)]
#[UsesClass(Container::class)]
#[UsesClass(Config::class)]
#[UsesClass(ConfigLoader::class)]
#[UsesClass(ConfigServiceProvider::class)]
#[UsesClass(Database::class)]
#[UsesClass(DatabaseServiceProvider::class)]
#[UsesClass(MigrationServiceProvider::class)]
#[UsesClass(Migrator::class)]
#[UsesClass(RouterServiceProvider::class)]
#[UsesClass(Route::class)]
#[UsesClass(Router::class)]
#[UsesClass(CoreServiceProviders::class)]
#[UsesClass(DefaultExceptionHandler::class)]
#[UsesClass(ExceptionHandlerServiceProvider::class)]
#[UsesClass(ServiceProvider::class)]
#[UsesClass(ConsoleServiceProvider::class)]
#[UsesClass(MigrateCommand::class)]
#[UsesClass(MigrateRollbackCommand::class)]
#[UsesClass(MakeMigrationCommand::class)]
#[UsesClass(MakeControllerCommand::class)]
#[UsesClass(MakeMiddlewareCommand::class)]
#[UsesClass(MakeProviderCommand::class)]
#[UsesClass(Console::class)]
#[UsesClass(Input::class)]
#[UsesClass(Output::class)]
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
