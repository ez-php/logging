<?php

declare(strict_types=1);

namespace EzPhp\Logging;

use EzPhp\Contracts\ExceptionHandlerInterface;
use EzPhp\Http\RequestInterface;
use EzPhp\Http\ResponseInterface;
use Throwable;

/**
 * Class LoggingExceptionHandler
 *
 * Decorator that logs every exception in report() and delegates both
 * report() and render() to the inner handler. render() does not log: the
 * kernel calls report() and render() separately, so logging in both would
 * record every exception twice. Registered automatically by LogServiceProvider.
 *
 * @package EzPhp\Logging
 */
final readonly class LoggingExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * LoggingExceptionHandler Constructor
     *
     * @param ExceptionHandlerInterface $inner  The real exception-to-response converter.
     * @param LoggerInterface           $logger Logger to record the exception.
     */
    public function __construct(
        private ExceptionHandlerInterface $inner,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Log the exception, then let the inner handler report it too.
     *
     * @param Throwable        $e
     * @param RequestInterface $request
     *
     * @return void
     */
    public function report(Throwable $e, RequestInterface $request): void
    {
        $this->logger->error($e->getMessage(), [
            'exception' => $e::class,
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        $this->inner->report($e, $request);
    }

    /**
     * Delegate rendering. Does not log — report() does, and the kernel calls both.
     *
     * @param Throwable        $e
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function render(Throwable $e, RequestInterface $request): ResponseInterface
    {
        return $this->inner->render($e, $request);
    }
}
