<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Logging\Log;
use EzPhp\Logging\LoggerInterface;
use EzPhp\Logging\NullDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;

/**
 * Class LogTest
 *
 * @package Tests
 */
#[CoversClass(Log::class)]
#[UsesClass(NullDriver::class)]
final class LogTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        Log::resetLogger();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Log::resetLogger();
    }

    /**
     * @return void
     */
    public function test_throws_when_logger_not_initialized(): void
    {
        $this->expectException(RuntimeException::class);
        Log::info('test');
    }

    /**
     * @return void
     */
    public function test_set_logger_wires_facade(): void
    {
        $spy = new class () implements LoggerInterface {
            /** @var list<array{level: string, message: string}> */
            public array $recorded = [];

            /** @param array<string, mixed> $context */
            public function log(string $level, string $message, array $context = []): void
            {
                $this->recorded[] = ['level' => $level, 'message' => $message];
            }

            /** @param array<string, mixed> $context */
            public function debug(string $message, array $context = []): void
            {
                $this->log('debug', $message);
            }

            /** @param array<string, mixed> $context */
            public function info(string $message, array $context = []): void
            {
                $this->log('info', $message);
            }

            /** @param array<string, mixed> $context */
            public function warning(string $message, array $context = []): void
            {
                $this->log('warning', $message);
            }

            /** @param array<string, mixed> $context */
            public function error(string $message, array $context = []): void
            {
                $this->log('error', $message);
            }

            /** @param array<string, mixed> $context */
            public function critical(string $message, array $context = []): void
            {
                $this->log('critical', $message);
            }
        };

        Log::setLogger($spy);

        Log::debug('a');
        Log::info('b');
        Log::warning('c');
        Log::error('d');
        Log::critical('e');
        Log::log('info', 'f');

        $this->assertCount(6, $spy->recorded);
        $this->assertSame('debug', $spy->recorded[0]['level']);
        $this->assertSame('a', $spy->recorded[0]['message']);
        $this->assertSame('critical', $spy->recorded[4]['level']);
    }

    /**
     * @return void
     */
    public function test_reset_logger_clears_the_facade(): void
    {
        Log::setLogger(new NullDriver());
        Log::resetLogger();

        $this->expectException(RuntimeException::class);
        Log::info('should throw');
    }
}
