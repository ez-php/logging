<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Logging\JsonDriver;
use EzPhp\Logging\LogLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Class JsonDriverTest
 *
 * @package Tests
 */
#[CoversClass(JsonDriver::class)]
#[UsesClass(LogLevel::class)]
final class JsonDriverTest extends TestCase
{
    /**
     * @return void
     */
    public function test_output_message_is_valid_json(): void
    {
        $spy = new SpyLogger();
        $driver = new JsonDriver($spy);

        $driver->log(LogLevel::INFO, 'hello', []);

        $this->assertCount(1, $spy->entries);
        $decoded = json_decode($spy->entries[0]['message'], true);
        $this->assertIsArray($decoded);
    }

    /**
     * @return void
     */
    public function test_json_contains_required_keys(): void
    {
        $spy = new SpyLogger();
        $driver = new JsonDriver($spy);

        $driver->log(LogLevel::ERROR, 'oops', ['code' => 42]);

        $decoded = json_decode($spy->entries[0]['message'], true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('timestamp', $decoded);
        $this->assertArrayHasKey('level', $decoded);
        $this->assertArrayHasKey('message', $decoded);
        $this->assertArrayHasKey('context', $decoded);
    }

    /**
     * @return void
     */
    public function test_original_context_in_json_context_field(): void
    {
        $spy = new SpyLogger();
        $driver = new JsonDriver($spy);

        $driver->log(LogLevel::WARNING, 'ctx test', ['user' => 'alice']);

        $decoded = json_decode($spy->entries[0]['message'], true);
        $this->assertIsArray($decoded);
        $this->assertSame(['user' => 'alice'], $decoded['context']);
    }

    /**
     * @return void
     */
    public function test_inner_driver_receives_empty_context(): void
    {
        $spy = new SpyLogger();
        $driver = new JsonDriver($spy);

        $driver->log(LogLevel::DEBUG, 'test', ['key' => 'val']);

        $this->assertSame([], $spy->entries[0]['context']);
    }

    /**
     * @return void
     */
    public function test_level_value_in_json(): void
    {
        $spy = new SpyLogger();
        $driver = new JsonDriver($spy);

        $driver->log(LogLevel::CRITICAL, 'critical msg', []);

        $decoded = json_decode($spy->entries[0]['message'], true);
        $this->assertIsArray($decoded);
        $this->assertSame('critical', $decoded['level']);
    }

    /**
     * @return void
     */
    public function test_debug_convenience_routes_through_log(): void
    {
        $spy = new SpyLogger();
        $driver = new JsonDriver($spy);

        $driver->debug('dbg');

        $this->assertCount(1, $spy->entries);
        $this->assertSame(LogLevel::DEBUG, $spy->entries[0]['level']);
    }

    /**
     * @return void
     */
    public function test_info_convenience_routes_through_log(): void
    {
        $spy = new SpyLogger();
        $driver = new JsonDriver($spy);

        $driver->info('inf');

        $this->assertCount(1, $spy->entries);
        $this->assertSame(LogLevel::INFO, $spy->entries[0]['level']);
    }

    /**
     * @return void
     */
    public function test_warning_convenience_routes_through_log(): void
    {
        $spy = new SpyLogger();
        $driver = new JsonDriver($spy);

        $driver->warning('warn');

        $this->assertCount(1, $spy->entries);
        $this->assertSame(LogLevel::WARNING, $spy->entries[0]['level']);
    }

    /**
     * @return void
     */
    public function test_error_convenience_routes_through_log(): void
    {
        $spy = new SpyLogger();
        $driver = new JsonDriver($spy);

        $driver->error('err');

        $this->assertCount(1, $spy->entries);
        $this->assertSame(LogLevel::ERROR, $spy->entries[0]['level']);
    }

    /**
     * @return void
     */
    public function test_critical_convenience_routes_through_log(): void
    {
        $spy = new SpyLogger();
        $driver = new JsonDriver($spy);

        $driver->critical('crit');

        $this->assertCount(1, $spy->entries);
        $this->assertSame(LogLevel::CRITICAL, $spy->entries[0]['level']);
    }
}
