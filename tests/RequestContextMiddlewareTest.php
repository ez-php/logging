<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Http\Request;
use EzPhp\Http\Response;
use EzPhp\Logging\ContextualLogger;
use EzPhp\Logging\Log;
use EzPhp\Logging\LogLevel;
use EzPhp\Logging\RequestContextMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Class RequestContextMiddlewareTest
 *
 * @package Tests
 */
#[CoversClass(RequestContextMiddleware::class)]
#[UsesClass(ContextualLogger::class)]
#[UsesClass(LogLevel::class)]
#[UsesClass(Log::class)]
final class RequestContextMiddlewareTest extends TestCase
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
    public function test_context_keys_present_in_log_entry(): void
    {
        $spy = new SpyLogger();
        Log::setLogger($spy);

        $middleware = new RequestContextMiddleware($spy);
        $request = new Request('GET', '/users', server: ['REMOTE_ADDR' => '1.2.3.4']);

        // Use Log facade inside the closure so the call passes through ContextualLogger
        $middleware->handle($request, function (Request $req): Response {
            Log::info('inside');
            return new Response('ok');
        });

        $this->assertNotEmpty($spy->entries);
        $entry = $spy->entries[0];
        $this->assertArrayHasKey('request_id', $entry['context']);
        $this->assertSame('1.2.3.4', $entry['context']['ip']);
        $this->assertSame('GET', $entry['context']['method']);
        $this->assertSame('/users', $entry['context']['path']);
    }

    /**
     * @return void
     */
    public function test_user_id_present_when_resolver_provided(): void
    {
        $spy = new SpyLogger();
        Log::setLogger($spy);

        $middleware = new RequestContextMiddleware($spy, fn () => 42);
        $request = new Request('POST', '/profile');

        $middleware->handle($request, function (Request $req): Response {
            Log::info('check');
            return new Response('ok');
        });

        $this->assertArrayHasKey('user_id', $spy->entries[0]['context']);
        $this->assertSame(42, $spy->entries[0]['context']['user_id']);
    }

    /**
     * @return void
     */
    public function test_no_user_id_when_no_resolver(): void
    {
        $spy = new SpyLogger();
        Log::setLogger($spy);

        $middleware = new RequestContextMiddleware($spy);
        $request = new Request('GET', '/');

        $middleware->handle($request, function (Request $req): Response {
            Log::info('check');
            return new Response('ok');
        });

        $this->assertArrayNotHasKey('user_id', $spy->entries[0]['context']);
    }

    /**
     * @return void
     */
    public function test_logger_restored_after_request(): void
    {
        $spy = new SpyLogger();
        Log::setLogger($spy);

        $middleware = new RequestContextMiddleware($spy);
        $request = new Request('GET', '/');

        $middleware->handle($request, fn (Request $req): Response => new Response('ok'));

        // After the middleware completes the original logger ($spy) is restored
        // in the facade. Log via facade — the entry should NOT have request_id
        // injected since ContextualLogger is no longer active.
        Log::info('after', ['plain' => true]);

        $last = $spy->entries[count($spy->entries) - 1];
        $this->assertArrayNotHasKey('request_id', $last['context']);
    }
}
