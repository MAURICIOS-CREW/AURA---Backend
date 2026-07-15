<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $responseBody = $response->getContent();
        $decodedBody = json_decode($responseBody, true);

        $logData = json_encode([
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'request' => $request->all(),
            'response' => $decodedBody ?? $responseBody,
        ]); // Sin JSON_PRETTY_PRINT

        file_put_contents('php://stderr', "API_LOG_DOCKER: " . $logData . "\n");

        return $response;
    }
}
