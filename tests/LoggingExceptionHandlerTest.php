<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Contracts\ExceptionHandlerInterface;
use EzPhp\Http\Request;
use EzPhp\Http\Response;
use EzPhp\Logging\LoggerInterface;
use EzPhp\Logging\LoggingExceptionHandler;
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
#[UsesClass(NullDriver::class)]
final class LoggingExceptionHandlerTest extends TestCase
{
    /**
     * @return void
     */
    public function test_logs_exception_message_and_metadata(): void
    {
        $spy = new class () implements LoggerInterface {
            /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
            public array $logged = [];

            /** @param array<string, mixed> $context */
            public function log(string $level, string $message, array $context = []): void
            {
                $this->logged[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }

            /** @param array<string, mixed> $context */
            public function debug(string $message, array $context = []): void
            {
                $this->log('debug', $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function info(string $message, array $context = []): void
            {
                $this->log('info', $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function warning(string $message, array $context = []): void
            {
                $this->log('warning', $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function error(string $message, array $context = []): void
            {
                $this->log('error', $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function critical(string $message, array $context = []): void
            {
                $this->log('critical', $message, $context);
            }
        };

        $inner = $this->makeInnerHandler(new Response('error', 500));
        $handler = new LoggingExceptionHandler($inner, $spy);
        $e = new RuntimeException('something broke', 42);

        $handler->render($e, new Request('GET', '/'));

        $this->assertCount(1, $spy->logged);
        $this->assertSame('error', $spy->logged[0]['level']);
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
    public function test_logs_before_delegating(): void
    {
        /** @var \ArrayObject<int, string> $order */
        $order = new \ArrayObject();

        $spy = new class ($order) implements LoggerInterface {
            /** @param \ArrayObject<int, string> $order */
            public function __construct(private readonly \ArrayObject $order)
            {
            }

            /** @param array<string, mixed> $context */
            public function log(string $level, string $message, array $context = []): void
            {
                $this->order->append('log');
            }

            /** @param array<string, mixed> $context */
            public function debug(string $message, array $context = []): void
            {
                $this->log('debug', $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function info(string $message, array $context = []): void
            {
                $this->log('info', $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function warning(string $message, array $context = []): void
            {
                $this->log('warning', $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function error(string $message, array $context = []): void
            {
                $this->log('error', $message, $context);
            }

            /** @param array<string, mixed> $context */
            public function critical(string $message, array $context = []): void
            {
                $this->log('critical', $message, $context);
            }
        };

        $inner = new class ($order) implements ExceptionHandlerInterface {
            /** @param \ArrayObject<int, string> $order */
            public function __construct(private readonly \ArrayObject $order)
            {
            }

            public function render(Throwable $e, Request $request): Response
            {
                $this->order->append('render');
                return new Response('ok');
            }
        };

        (new LoggingExceptionHandler($inner, $spy))->render(new RuntimeException('x'), new Request('GET', '/'));

        $this->assertSame(['log', 'render'], $order->getArrayCopy());
    }

    /**
     * @param Response $response
     *
     * @return ExceptionHandlerInterface
     */
    private function makeInnerHandler(Response $response): ExceptionHandlerInterface
    {
        return new class ($response) implements ExceptionHandlerInterface {
            public function __construct(private readonly Response $response)
            {
            }

            public function render(Throwable $e, Request $request): Response
            {
                return $this->response;
            }
        };
    }
}
