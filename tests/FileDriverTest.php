<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Logging\FileDriver;
use EzPhp\Logging\LogLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Class FileDriverTest
 *
 * @package Tests
 */
#[CoversClass(FileDriver::class)]
#[UsesClass(LogLevel::class)]
final class FileDriverTest extends TestCase
{
    private string $dir;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/ez-php-log-test-' . uniqid();
        mkdir($this->dir);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*.log') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->dir)) {
            rmdir($this->dir);
        }
    }

    /**
     * @return void
     */
    public function test_log_creates_daily_file(): void
    {
        $driver = new FileDriver($this->dir);
        $driver->log(LogLevel::INFO, 'hello');

        $expected = $this->dir . '/app-' . date('Y-m-d') . '.log';
        $this->assertFileExists($expected);
    }

    /**
     * @return void
     */
    public function test_log_writes_level_and_message(): void
    {
        $driver = new FileDriver($this->dir);
        $driver->log(LogLevel::WARNING, 'something happened');

        $content = $this->readLog();
        $this->assertStringContainsString('WARNING', $content);
        $this->assertStringContainsString('something happened', $content);
    }

    /**
     * @return void
     */
    public function test_log_includes_context_as_json(): void
    {
        $driver = new FileDriver($this->dir);
        $driver->log(LogLevel::ERROR, 'oops', ['key' => 'val', 'num' => 42]);

        $content = $this->readLog();
        $this->assertStringContainsString('"key":"val"', $content);
        $this->assertStringContainsString('"num":42', $content);
    }

    /**
     * @return void
     */
    public function test_log_omits_context_when_empty(): void
    {
        $driver = new FileDriver($this->dir);
        $driver->log(LogLevel::DEBUG, 'no context');

        $content = $this->readLog();
        $this->assertStringNotContainsString('{', $content);
    }

    /**
     * @return void
     */
    public function test_log_creates_directory_if_missing(): void
    {
        $nested = $this->dir . '/nested/path';
        $driver = new FileDriver($nested);
        $driver->log(LogLevel::INFO, 'test');

        $this->assertDirectoryExists($nested);

        foreach (glob($nested . '/*.log') ?: [] as $file) {
            unlink($file);
        }
        rmdir($nested);
        rmdir($this->dir . '/nested');
    }

    /**
     * @return void
     */
    public function test_log_appends_multiple_entries(): void
    {
        $driver = new FileDriver($this->dir);
        $driver->log(LogLevel::INFO, 'first');
        $driver->log(LogLevel::INFO, 'second');

        $content = $this->readLog();
        $this->assertStringContainsString('first', $content);
        $this->assertStringContainsString('second', $content);
    }

    /**
     * @return void
     */
    public function test_debug_helper_writes_debug_level(): void
    {
        (new FileDriver($this->dir))->debug('dbg msg');
        $this->assertStringContainsString('DEBUG', $this->readLog());
    }

    /**
     * @return void
     */
    public function test_info_helper_writes_info_level(): void
    {
        (new FileDriver($this->dir))->info('info msg');
        $this->assertStringContainsString('INFO', $this->readLog());
    }

    /**
     * @return void
     */
    public function test_warning_helper_writes_warning_level(): void
    {
        (new FileDriver($this->dir))->warning('warn msg');
        $this->assertStringContainsString('WARNING', $this->readLog());
    }

    /**
     * @return void
     */
    public function test_error_helper_writes_error_level(): void
    {
        (new FileDriver($this->dir))->error('err msg');
        $this->assertStringContainsString('ERROR', $this->readLog());
    }

    /**
     * @return void
     */
    public function test_critical_helper_writes_critical_level(): void
    {
        (new FileDriver($this->dir))->critical('crit msg');
        $this->assertStringContainsString('CRITICAL', $this->readLog());
    }

    /**
     * @return string
     */
    private function readLog(): string
    {
        $file = $this->dir . '/app-' . date('Y-m-d') . '.log';
        return file_get_contents($file) ?: '';
    }
}
