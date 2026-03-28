<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Logging\ContextualLogger;
use EzPhp\Logging\LogLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Class ContextualLoggerTest
 *
 * @package Tests
 */
#[CoversClass(ContextualLogger::class)]
#[UsesClass(LogLevel::class)]
final class ContextualLoggerTest extends TestCase
{
    /**
     * @return void
     */
    public function test_default_context_merged_into_every_log_call(): void
    {
        $spy = new SpyLogger();
        $logger = new ContextualLogger($spy, ['request_id' => 'abc123']);

        $logger->log(LogLevel::INFO, 'hello', []);

        $this->assertCount(1, $spy->entries);
        $this->assertSame('abc123', $spy->entries[0]['context']['request_id']);
    }

    /**
     * @return void
     */
    public function test_call_context_is_merged_over_default_context(): void
    {
        $spy = new SpyLogger();
        $logger = new ContextualLogger($spy, ['key' => 'default', 'fixed' => 'yes']);

        $logger->log(LogLevel::WARNING, 'msg', ['key' => 'override', 'extra' => 'data']);

        $this->assertCount(1, $spy->entries);
        $this->assertSame('override', $spy->entries[0]['context']['key']);
        $this->assertSame('yes', $spy->entries[0]['context']['fixed']);
        $this->assertSame('data', $spy->entries[0]['context']['extra']);
    }

    /**
     * @return void
     */
    public function test_debug_convenience_method_delegates(): void
    {
        $spy = new SpyLogger();
        $logger = new ContextualLogger($spy, ['x' => 1]);

        $logger->debug('dbg');

        $this->assertSame(LogLevel::DEBUG, $spy->entries[0]['level']);
        $this->assertSame('dbg', $spy->entries[0]['message']);
        $this->assertSame(1, $spy->entries[0]['context']['x']);
    }

    /**
     * @return void
     */
    public function test_info_convenience_method_delegates(): void
    {
        $spy = new SpyLogger();
        $logger = new ContextualLogger($spy, ['x' => 2]);

        $logger->info('inf');

        $this->assertSame(LogLevel::INFO, $spy->entries[0]['level']);
        $this->assertSame(2, $spy->entries[0]['context']['x']);
    }

    /**
     * @return void
     */
    public function test_warning_convenience_method_delegates(): void
    {
        $spy = new SpyLogger();
        $logger = new ContextualLogger($spy, ['x' => 3]);

        $logger->warning('warn');

        $this->assertSame(LogLevel::WARNING, $spy->entries[0]['level']);
    }

    /**
     * @return void
     */
    public function test_error_convenience_method_delegates(): void
    {
        $spy = new SpyLogger();
        $logger = new ContextualLogger($spy, []);

        $logger->error('err');

        $this->assertSame(LogLevel::ERROR, $spy->entries[0]['level']);
    }

    /**
     * @return void
     */
    public function test_critical_convenience_method_delegates(): void
    {
        $spy = new SpyLogger();
        $logger = new ContextualLogger($spy, []);

        $logger->critical('crit');

        $this->assertSame(LogLevel::CRITICAL, $spy->entries[0]['level']);
    }
}
