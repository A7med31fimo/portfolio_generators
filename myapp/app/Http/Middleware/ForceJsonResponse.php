<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force every API request to expect JSON.
 *
 * Without this, Laravel returns HTML error pages when the client
 * doesn't send 'Accept: application/json'. This middleware ensures
 * the exception handler always returns JSON — even for browser requests
 * that hit the API directly.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
