<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Logging\LogLevel;
use EzPhp\Logging\StdoutDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Class StdoutDriverTest
 *
 * error() and critical() write to STDERR (not captured by ob_start), so those tests
 * assert that nothing appears on STDOUT. Actual STDERR content is verified structurally
 * via the shared format logic tested through STDOUT-level tests.
 *
 * @package Tests
 */
#[CoversClass(StdoutDriver::class)]
#[UsesClass(LogLevel::class)]
final class StdoutDriverTest extends TestCase
{
    /**
     * @return void
     */
    public function test_debug_writes_to_stdout(): void
    {
        $driver = new StdoutDriver();

        ob_start();
        $driver->debug('debug message');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('DEBUG', $output);
        $this->assertStringContainsString('debug message', $output);
    }

    /**
     * @return void
     */
    public function test_info_writes_to_stdout(): void
    {
        $driver = new StdoutDriver();

        ob_start();
        $driver->info('info message');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('INFO', $output);
        $this->assertStringContainsString('info message', $output);
    }

    /**
     * @return void
     */
    public function test_warning_writes_to_stdout(): void
    {
        $driver = new StdoutDriver();

        ob_start();
        $driver->warning('warn message');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('WARNING', $output);
        $this->assertStringContainsString('warn message', $output);
    }

    /**
     * @return void
     */
    public function test_error_does_not_write_to_stdout(): void
    {
        $driver = new StdoutDriver();

        ob_start();
        $driver->error('error message');
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
    }

    /**
     * @return void
     */
    public function test_critical_does_not_write_to_stdout(): void
    {
        $driver = new StdoutDriver();

        ob_start();
        $driver->critical('critical message');
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
    }

    /**
     * @return void
     */
    public function test_log_includes_context_as_json(): void
    {
        $driver = new StdoutDriver();

        ob_start();
        $driver->info('ctx test', ['foo' => 'bar']);
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('"foo":"bar"', $output);
    }

    /**
     * @return void
     */
    public function test_log_omits_context_when_empty(): void
    {
        $driver = new StdoutDriver();

        ob_start();
        $driver->info('no ctx');
        $output = (string) ob_get_clean();

        $this->assertStringNotContainsString('{', $output);
    }

    /**
     * @return void
     */
    public function test_log_dispatches_via_level_helpers(): void
    {
        $driver = new StdoutDriver();

        ob_start();
        $driver->log(LogLevel::INFO, 'via log()');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('INFO', $output);
        $this->assertStringContainsString('via log()', $output);
    }
}
