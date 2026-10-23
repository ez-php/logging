<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Logging\LogLevel;
use EzPhp\Logging\SyslogDriver;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * SyslogDriver with an injected writer, so no system logger is needed.
 *
 * @package Tests
 */
#[CoversClass(SyslogDriver::class)]
#[UsesClass(LogLevel::class)]
final class SyslogDriverTest extends TestCase
{
    /** @var list<array{int, string}> */
    private array $written = [];

    private function driver(string $facility = 'user'): SyslogDriver
    {
        return new SyslogDriver('test-app', $facility, function (int $priority, string $line): void {
            $this->written[] = [$priority, $line];
        });
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function levels(): array
    {
        return [
            'debug' => ['debug', LOG_DEBUG],
            'info' => ['info', LOG_INFO],
            'warning' => ['warning', LOG_WARNING],
            'error' => ['error', LOG_ERR],
            'critical' => ['critical', LOG_CRIT],
        ];
    }

    #[DataProvider('levels')]
    public function test_each_level_maps_to_its_syslog_priority(string $method, int $priority): void
    {
        $this->driver()->{$method}('hello');

        $this->assertSame([[$priority, 'hello']], $this->written);
    }

    public function test_context_is_appended_as_json(): void
    {
        $this->driver()->log(LogLevel::INFO, 'order placed', ['id' => 7, 'total' => '9.90']);

        $this->assertSame([[LOG_INFO, 'order placed {"id":7,"total":"9.90"}']], $this->written);
    }

    public function test_facility_names_map_to_constants(): void
    {
        $this->assertSame(LOG_USER, $this->driver()->facility());
        $this->assertSame(LOG_LOCAL5, $this->driver('local5')->facility());
        $this->assertSame(LOG_DAEMON, $this->driver('daemon')->facility());
    }

    public function test_unknown_facility_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown syslog facility "kernel"');

        $this->driver('kernel');
    }

    public function test_default_writer_reaches_the_system_logger_without_error(): void
    {
        // Without a writer the driver calls openlog()/syslog(); in a container without a
        // syslog daemon that is a silent no-op, so this only proves the call path works.
        (new SyslogDriver('ez-php-test'))->info('syslog driver smoke test');

        $this->addToAssertionCount(1);
    }
}
