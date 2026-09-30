<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Application\Application;
use EzPhp\Contracts\ConfigInterface;
use EzPhp\Contracts\ContainerInterface;
use EzPhp\Contracts\ExceptionHandlerInterface;
use EzPhp\Logging\FileDriver;
use EzPhp\Logging\JsonDriver;
use EzPhp\Logging\Log;
use EzPhp\Logging\LoggerInterface;
use EzPhp\Logging\LoggingExceptionHandler;
use EzPhp\Logging\LogLevel;
use EzPhp\Logging\LogServiceProvider;
use EzPhp\Logging\MinLevelDriver;
use EzPhp\Logging\NullDriver;
use EzPhp\Logging\StackDriver;
use EzPhp\Logging\StdoutDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
#[UsesClass(JsonDriver::class)]
#[UsesClass(StackDriver::class)]
#[UsesClass(MinLevelDriver::class)]
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

    // ─── register(): driver resolution against a minimal container ────────────

    /**
     * Resolve LoggerInterface through the provider's binding with the given config.
     *
     * @param array<string, mixed> $config
     *
     * @return LoggerInterface
     */
    private function resolveLogger(array $config): LoggerInterface
    {
        $configInstance = new class ($config) implements ConfigInterface {
            /** @param array<string, mixed> $data */
            public function __construct(private readonly array $data)
            {
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->data[$key] ?? $default;
            }
        };

        $container = new class ($configInstance) implements ContainerInterface {
            /** @var array<string, callable> */
            private array $bindings = [];

            public function __construct(private readonly ConfigInterface $config)
            {
            }

            public function bind(string $abstract, string|callable|null $factory = null): static
            {
                if (is_callable($factory)) {
                    $this->bindings[$abstract] = $factory;
                }

                return $this;
            }

            public function make(string $abstract): mixed
            {
                return $abstract === ConfigInterface::class ? $this->config : ($this->bindings[$abstract])($this);
            }

            public function has(string $abstract): bool
            {
                return isset($this->bindings[$abstract]);
            }

            public function instance(string $abstract, object $instance): void
            {
            }
        };

        (new LogServiceProvider($container))->register();
        $logger = $container->make(LoggerInterface::class);
        $this->assertInstanceOf(LoggerInterface::class, $logger);

        return $logger;
    }

    /**
     * @return array<string, array{array<string, mixed>, class-string<LoggerInterface>}>
     */
    public static function drivers(): array
    {
        return [
            'file' => [['logging.driver' => 'file'], FileDriver::class],
            'stdout' => [['logging.driver' => 'stdout'], StdoutDriver::class],
            'null' => [['logging.driver' => 'null'], NullDriver::class],
            'json' => [['logging.driver' => 'json'], JsonDriver::class],
            'stack' => [['logging.driver' => 'stack', 'logging.stack' => ['null']], StackDriver::class],
            'unknown falls back to file' => [['logging.driver' => 'syslog'], FileDriver::class],
            'non-string falls back to file' => [['logging.driver' => 1], FileDriver::class],
            'valid min_level wraps the driver' => [['logging.driver' => 'null', 'logging.min_level' => 'warning'], MinLevelDriver::class],
            'invalid min_level is ignored' => [['logging.driver' => 'null', 'logging.min_level' => 'loud'], NullDriver::class],
        ];
    }

    /**
     * @param array<string, mixed>          $config
     * @param class-string<LoggerInterface> $expected
     *
     * @return void
     */
    #[DataProvider('drivers')]
    public function test_configured_driver_resolves_to_its_class(array $config, string $expected): void
    {
        $this->assertInstanceOf($expected, $this->resolveLogger($config));
    }

    /**
     * The stack builds file/stdout/null sub-drivers, skipping unknown and
     * non-string entries; the file sub-driver writes under logging.path.
     *
     * @return void
     */
    public function test_stack_driver_builds_known_sub_drivers_with_configured_path(): void
    {
        $dir = sys_get_temp_dir() . '/ez-log-stack-' . bin2hex(random_bytes(4));

        try {
            $logger = $this->resolveLogger([
                'logging.driver' => 'stack',
                'logging.stack' => ['file', 'syslog', 42, 'null'],
                'logging.path' => $dir,
            ]);
            $logger->error('stacked message');

            $files = glob($dir . '/*') ?: [];
            $this->assertCount(1, $files);
            $this->assertStringContainsString('stacked message', (string) file_get_contents($files[0]));
        } finally {
            exec('rm -rf ' . escapeshellarg($dir));
        }
    }

    /**
     * @return void
     */
    public function test_json_driver_wraps_the_configured_inner_driver(): void
    {
        $dir = sys_get_temp_dir() . '/ez-log-json-' . bin2hex(random_bytes(4));

        try {
            $logger = $this->resolveLogger([
                'logging.driver' => 'json',
                'logging.json_inner' => 'file',
                'logging.path' => $dir,
            ]);
            $logger->info('json message', ['id' => 7]);

            $files = glob($dir . '/*') ?: [];
            $this->assertCount(1, $files);
            $contents = (string) file_get_contents($files[0]);
            $this->assertStringContainsString('json message', $contents);
            $this->assertStringContainsString('"id":7', $contents);
        } finally {
            exec('rm -rf ' . escapeshellarg($dir));
        }
    }
}
