<?php

declare(strict_types=1);

namespace EzPhp\Logging;

use EzPhp\Contracts\ExceptionHandlerInterface;
use EzPhp\Http\Request;
use EzPhp\Http\Response;
use Throwable;

/**
 * Class LoggingExceptionHandler
 *
 * Decorator that logs every exception before delegating HTTP rendering
 * to the inner handler. Registered automatically by LogServiceProvider.
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
     * @param Throwable $e
     * @param Request   $request
     *
     * @return Response
     */
    public function render(Throwable $e, Request $request): Response
    {
        $this->logger->error($e->getMessage(), [
            'exception' => $e::class,
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return $this->inner->render($e, $request);
    }
}
