<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Contracts\ExceptionHandlerInterface;
use EzPhp\Http\Request;
use EzPhp\Http\RequestInterface;
use EzPhp\Http\Response;
use EzPhp\Logging\LoggerInterface;
use EzPhp\Logging\LoggingExceptionHandler;
use EzPhp\Logging\LogLevel;
use EzPhp\Logging\NullDriver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use Throwable;

/**
 * Class LoggingExceptionHandlerTest
 *
 * @package Tests
 */
#[CoversClass(LoggingExceptionHandler::class)]
#[UsesClass(LogLevel::class)]
#[UsesClass(NullDriver::class)]
final class LoggingExceptionHandlerTest extends TestCase
{
    /**
     * @return void
     */
    public function test_logs_exception_message_and_metadata(): void
    {
        $spy = new class () implements LoggerInterface {
            /** @var list<array{level: LogLevel, message: string, context: array<string, mixed>}> */
            public array $logged = [];

            /** @param array<string, mixed> $context */
            public function log(LogLevel $level, string $message, array $context = []): void
            {
                $this->logged[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }

            /** @param array<string, mixed> $context */
            public function debug(string $message, array $context = []): void
            {
                $this->log(LogLevel::DEBUG, $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function info(string $message, array $context = []): void
            {
                $this->log(LogLevel::INFO, $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function warning(string $message, array $context = []): void
            {
                $this->log(LogLevel::WARNING, $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function error(string $message, array $context = []): void
            {
                $this->log(LogLevel::ERROR, $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function critical(string $message, array $context = []): void
            {
                $this->log(LogLevel::CRITICAL, $message, $context);
            }
        };

        $inner = $this->makeInnerHandler(new Response('error', 500));
        $handler = new LoggingExceptionHandler($inner, $spy);
        $e = new RuntimeException('something broke', 42);

        $handler->report($e, new Request('GET', '/'));

        $this->assertCount(1, $spy->logged);
        $this->assertSame(LogLevel::ERROR, $spy->logged[0]['level']);
        $this->assertSame('something broke', $spy->logged[0]['message']);
        $this->assertSame(RuntimeException::class, $spy->logged[0]['context']['exception']);
        $this->assertSame(42, $spy->logged[0]['context']['code']);
    }

    /**
     * @return void
     */
    public function test_delegates_rendering_to_inner_handler(): void
    {
        $expectedResponse = new Response('from inner', 503);
        $inner = $this->makeInnerHandler($expectedResponse);
        $handler = new LoggingExceptionHandler($inner, new NullDriver());

        $response = $handler->render(new RuntimeException('x'), new Request('GET', '/'));

        $this->assertSame($expectedResponse, $response);
    }

    /**
     * @return void
     */
    public function test_report_logs_then_delegates_report_to_inner(): void
    {
        /** @var \ArrayObject<int, string> $order */
        $order = new \ArrayObject();
        $spy = $this->makeOrderLogger($order);

        $inner = new readonly class ($order) implements ExceptionHandlerInterface {
            /** @param \ArrayObject<int, string> $order */
            public function __construct(private \ArrayObject $order)
            {
            }

            public function report(Throwable $e, RequestInterface $request): void
            {
                $this->order->append('inner-report');
            }

            public function render(Throwable $e, RequestInterface $request): Response
            {
                $this->order->append('inner-render');
                return new Response('ok');
            }
        };

        (new LoggingExceptionHandler($inner, $spy))->report(new RuntimeException('x'), new Request('GET', '/'));

        $this->assertSame(['log', 'inner-report'], $order->getArrayCopy());
    }

    /**
     * render() must not log: the kernel calls report() and render() separately,
     * so logging in both would record every exception twice.
     *
     * @return void
     */
    public function test_render_does_not_log(): void
    {
        /** @var \ArrayObject<int, string> $order */
        $order = new \ArrayObject();
        $inner = $this->makeInnerHandler(new Response('error', 500));

        (new LoggingExceptionHandler($inner, $this->makeOrderLogger($order)))
            ->render(new RuntimeException('x'), new Request('GET', '/'));

        $this->assertSame([], $order->getArrayCopy());
    }

    /**
     * @param \ArrayObject<int, string> $order
     *
     * @return LoggerInterface
     */
    private function makeOrderLogger(\ArrayObject $order): LoggerInterface
    {
        return new readonly class ($order) implements LoggerInterface {
            /** @param \ArrayObject<int, string> $order */
            public function __construct(private \ArrayObject $order)
            {
            }

            /** @param array<string, mixed> $context */
            public function log(LogLevel $level, string $message, array $context = []): void
            {
                $this->order->append('log');
            }

            /** @param array<string, mixed> $context */
            public function debug(string $message, array $context = []): void
            {
                $this->log(LogLevel::DEBUG, $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function info(string $message, array $context = []): void
            {
                $this->log(LogLevel::INFO, $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function warning(string $message, array $context = []): void
            {
                $this->log(LogLevel::WARNING, $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function error(string $message, array $context = []): void
            {
                $this->log(LogLevel::ERROR, $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function critical(string $message, array $context = []): void
            {
                $this->log(LogLevel::CRITICAL, $message, $context);
            }
        };
    }

    /**
     * @param Response $response
     *
     * @return ExceptionHandlerInterface
     */
    private function makeInnerHandler(Response $response): ExceptionHandlerInterface
    {
        return new readonly class ($response) implements ExceptionHandlerInterface {
            public function __construct(private Response $response)
            {
            }

            public function report(Throwable $e, RequestInterface $request): void
            {
            }

            public function render(Throwable $e, RequestInterface $request): Response
            {
                return $this->response;
            }
        };
    }
}
