<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Contracts\ConfigInterface;
use EzPhp\Contracts\ContainerInterface;
use EzPhp\Contracts\ExceptionHandlerInterface;
use EzPhp\Http\Request;
use EzPhp\Http\Response;
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
use Throwable;

/**
 * Class LogServiceProviderTest
 *
 * Uses a minimal ContainerInterface stub instead of Application so that this
 * test does not require ez-php/framework as a dev dependency.
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
final class LogServiceProviderTest extends TestCase
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
     */
    public function test_logger_interface_is_bound(): void
    {
        $container = $this->makeContainer();

        $this->assertInstanceOf(LoggerInterface::class, $container->make(LoggerInterface::class));
    }

    /**
     * @return void
     */
    public function test_exception_handler_is_wrapped_with_logging_decorator(): void
    {
        $container = $this->makeContainer();

        $this->assertInstanceOf(LoggingExceptionHandler::class, $container->make(ExceptionHandlerInterface::class));
    }

    /**
     * @return void
     */
    public function test_log_facade_is_wired_after_boot(): void
    {
        $container = $this->makeContainer();

        // Facade should be wired — calling it must not throw
        ob_start();
        Log::info('facade test');
        ob_end_clean();

        $this->assertInstanceOf(LoggerInterface::class, $container->make(LoggerInterface::class));
    }

    /**
     * @return void
     */
    public function test_file_driver_is_selected_by_default(): void
    {
        $container = $this->makeContainer();

        $this->assertInstanceOf(FileDriver::class, $container->make(LoggerInterface::class));
    }

    /**
     * @return void
     */
    public function test_stdout_driver_is_instantiatable(): void
    {
        $this->assertInstanceOf(StdoutDriver::class, new StdoutDriver());
    }

    /**
     * Bootstrap a minimal container with LogServiceProvider registered and booted.
     *
     * @return ProviderTestContainer
     */
    private function makeContainer(): ProviderTestContainer
    {
        $container = new ProviderTestContainer();

        // Pre-bind ConfigInterface with a no-op stub (no driver key → defaults to 'file')
        $container->bind(ConfigInterface::class, fn () => new class () implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                return $default;
            }
        });

        // Pre-bind ExceptionHandlerInterface with a no-op inner handler
        $container->bind(ExceptionHandlerInterface::class, fn () => new class () implements ExceptionHandlerInterface {
            public function render(Throwable $e, Request $request): Response
            {
                return new Response('', 500);
            }
        });

        $provider = new LogServiceProvider($container);
        $provider->register();
        $provider->boot();

        return $container;
    }
}

/**
 * Minimal ContainerInterface implementation for use in tests.
 * Avoids pulling in ez-php/framework as a dev dependency.
 *
 * @package Tests
 */
class ProviderTestContainer implements ContainerInterface
{
    /** @var array<string, callable> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    /**
     * @param string                   $abstract
     * @param string|callable|null     $factory
     *
     * @return void
     */
    public function bind(string $abstract, string|callable|null $factory = null): void
    {
        if (is_callable($factory)) {
            $this->bindings[$abstract] = $factory;
        }
    }

    /**
     * @template T of object
     * @param class-string<T> $abstract
     *
     * @return T
     */
    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            /** @var T */
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            /** @var T $resolved */
            $resolved = ($this->bindings[$abstract])($this);
            $this->instances[$abstract] = $resolved;
            return $resolved;
        }

        throw new \RuntimeException("Cannot resolve '$abstract' from test container");
    }

    /**
     * @template T of object
     * @param class-string<T> $abstract
     * @param T               $instance
     *
     * @return void
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }
}
