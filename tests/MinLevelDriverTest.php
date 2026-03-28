<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Logging\LogLevel;
use EzPhp\Logging\MinLevelDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Class MinLevelDriverTest
 *
 * @package Tests
 */
#[CoversClass(MinLevelDriver::class)]
#[UsesClass(LogLevel::class)]
final class MinLevelDriverTest extends TestCase
{
    /**
     * @return void
     */
    public function test_message_at_exact_min_level_is_forwarded(): void
    {
        $spy = new SpyLogger();
        $driver = new MinLevelDriver($spy, LogLevel::WARNING);

        $driver->log(LogLevel::WARNING, 'exact min', []);

        $this->assertCount(1, $spy->entries);
        $this->assertSame(LogLevel::WARNING, $spy->entries[0]['level']);
    }

    /**
     * @return void
     */
    public function test_message_below_min_level_is_dropped(): void
    {
        $spy = new SpyLogger();
        $driver = new MinLevelDriver($spy, LogLevel::WARNING);

        $driver->log(LogLevel::INFO, 'too low', []);

        $this->assertCount(0, $spy->entries);
    }

    /**
     * @return void
     */
    public function test_debug_dropped_with_warning_minimum(): void
    {
        $spy = new SpyLogger();
        $driver = new MinLevelDriver($spy, LogLevel::WARNING);

        $driver->debug('dbg');

        $this->assertCount(0, $spy->entries);
    }

    /**
     * @return void
     */
    public function test_info_dropped_with_warning_minimum(): void
    {
        $spy = new SpyLogger();
        $driver = new MinLevelDriver($spy, LogLevel::WARNING);

        $driver->info('inf');

        $this->assertCount(0, $spy->entries);
    }

    /**
     * @return void
     */
    public function test_warning_forwarded_with_warning_minimum(): void
    {
        $spy = new SpyLogger();
        $driver = new MinLevelDriver($spy, LogLevel::WARNING);

        $driver->warning('warn');

        $this->assertCount(1, $spy->entries);
    }

    /**
     * @return void
     */
    public function test_error_forwarded_with_warning_minimum(): void
    {
        $spy = new SpyLogger();
        $driver = new MinLevelDriver($spy, LogLevel::WARNING);

        $driver->error('err');

        $this->assertCount(1, $spy->entries);
        $this->assertSame(LogLevel::ERROR, $spy->entries[0]['level']);
    }

    /**
     * @return void
     */
    public function test_critical_forwarded_with_warning_minimum(): void
    {
        $spy = new SpyLogger();
        $driver = new MinLevelDriver($spy, LogLevel::WARNING);

        $driver->critical('crit');

        $this->assertCount(1, $spy->entries);
        $this->assertSame(LogLevel::CRITICAL, $spy->entries[0]['level']);
    }

    /**
     * @return void
     */
    public function test_all_levels_forwarded_with_debug_minimum(): void
    {
        $spy = new SpyLogger();
        $driver = new MinLevelDriver($spy, LogLevel::DEBUG);

        foreach (LogLevel::all() as $level) {
            $driver->log($level, $level->value, []);
        }

        $this->assertCount(5, $spy->entries);
    }
}
