<?php

declare(strict_types=1);

namespace EzPhp\Logging;

use Closure;
use EzPhp\Contracts\MiddlewareInterface;
use EzPhp\Http\Request;
use EzPhp\Http\Response;

/**
 * Class RequestContextMiddleware
 *
 * Injects request-scoped context (request ID, IP, method, path, optional user ID)
 * into the Log facade for the duration of each HTTP request.
 *
 * The original logger is always restored after the request completes, even when
 * an exception is thrown, via a try/finally block.
 *
 * @package EzPhp\Logging
 */
final readonly class RequestContextMiddleware implements MiddlewareInterface
{
    /**
     * RequestContextMiddleware Constructor
     *
     * @param LoggerInterface $logger       The base logger to wrap with request context.
     * @param Closure|null    $userResolver Optional closure returning the current user ID (mixed).
     */
    public function __construct(
        private LoggerInterface $logger,
        private ?Closure $userResolver = null,
    ) {
    }

    /**
     * Wrap the logger with request context for the duration of this request.
     *
     * @param Request  $request
     * @param callable $next
     *
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        $remoteAddr = $request->server('REMOTE_ADDR', '');
        $ip = is_string($remoteAddr) ? $remoteAddr : '';

        $uri = $request->uri();
        $parsed = parse_url($uri, PHP_URL_PATH);
        $path = is_string($parsed) ? $parsed : '/';

        /** @var array<string, mixed> $context */
        $context = [
            'request_id' => bin2hex(random_bytes(8)),
            'ip' => $ip,
            'method' => $request->method(),
            'path' => $path,
        ];

        if ($this->userResolver !== null) {
            $context['user_id'] = ($this->userResolver)();
        }

        $contextual = new ContextualLogger($this->logger, $context);

        Log::setLogger($contextual);

        try {
            $response = $next($request);
        } finally {
            Log::setLogger($this->logger);
        }

        return $response;
    }
}
