<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Logging\LogLevel;
use EzPhp\Logging\StackDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Class StackDriverTest
 *
 * @package Tests
 */
#[CoversClass(StackDriver::class)]
#[UsesClass(LogLevel::class)]
final class StackDriverTest extends TestCase
{
    /**
     * @return void
     */
    public function test_all_drivers_receive_log_call(): void
    {
        $spy1 = new SpyLogger();
        $spy2 = new SpyLogger();
        $driver = new StackDriver([$spy1, $spy2]);

        $driver->log(LogLevel::INFO, 'broadcast', []);

        $this->assertCount(1, $spy1->entries);
        $this->assertCount(1, $spy2->entries);
    }

    /**
     * @return void
     */
    public function test_order_is_preserved(): void
    {
        $order = new OrderTracker();
        $first = new OrderedSpyLogger('first', $order);
        $second = new OrderedSpyLogger('second', $order);

        $driver = new StackDriver([$first, $second]);
        $driver->log(LogLevel::DEBUG, 'order', []);

        $this->assertSame(['first', 'second'], $order->calls);
    }

    /**
     * @return void
     */
    public function test_single_driver_stack_works(): void
    {
        $spy = new SpyLogger();
        $driver = new StackDriver([$spy]);

        $driver->log(LogLevel::WARNING, 'single', ['k' => 'v']);

        $this->assertCount(1, $spy->entries);
        $this->assertSame('single', $spy->entries[0]['message']);
        $this->assertSame(['k' => 'v'], $spy->entries[0]['context']);
    }

    /**
     * @return void
     */
    public function test_empty_stack_is_noop(): void
    {
        $driver = new StackDriver([]);
        $driver->log(LogLevel::ERROR, 'noop', []);

        // No assertion needed — test passes if no exception is thrown.
        $this->addToAssertionCount(1);
    }

    /**
     * @return void
     */
    public function test_debug_convenience_method_fans_out(): void
    {
        $spy = new SpyLogger();
        $driver = new StackDriver([$spy]);

        $driver->debug('dbg');

        $this->assertSame(LogLevel::DEBUG, $spy->entries[0]['level']);
    }

    /**
     * @return void
     */
    public function test_info_convenience_method_fans_out(): void
    {
        $spy = new SpyLogger();
        $driver = new StackDriver([$spy]);

        $driver->info('inf');

        $this->assertSame(LogLevel::INFO, $spy->entries[0]['level']);
    }

    /**
     * @return void
     */
    public function test_warning_convenience_method_fans_out(): void
    {
        $spy = new SpyLogger();
        $driver = new StackDriver([$spy]);

        $driver->warning('warn');

        $this->assertSame(LogLevel::WARNING, $spy->entries[0]['level']);
    }

    /**
     * @return void
     */
    public function test_error_convenience_method_fans_out(): void
    {
        $spy = new SpyLogger();
        $driver = new StackDriver([$spy]);

        $driver->error('err');

        $this->assertSame(LogLevel::ERROR, $spy->entries[0]['level']);
    }

    /**
     * @return void
     */
    public function test_critical_convenience_method_fans_out(): void
    {
        $spy = new SpyLogger();
        $driver = new StackDriver([$spy]);

        $driver->critical('crit');

        $this->assertSame(LogLevel::CRITICAL, $spy->entries[0]['level']);
    }
}
