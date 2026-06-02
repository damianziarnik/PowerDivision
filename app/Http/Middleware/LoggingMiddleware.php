<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Log every response in format:
     * {method} {uri} {contentType} {statusCode} {responseTime}
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);
        $contentType    = $response->headers->get('Content-Type', 'application/json');

        $this->logger->info(sprintf(
            '%s %s %s %d %dms',
            $request->method(),
            $request->getRequestUri(),
            $contentType,
            $response->getStatusCode(),
            $responseTimeMs
        ));

        return $response;
    }
}

