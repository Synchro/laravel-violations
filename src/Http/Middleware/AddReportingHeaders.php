<?php

declare(strict_types=1);

namespace Synchro\Violation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Synchro\Violation\Violation;

/**
 * Middleware to add the Reporting-Endpoints and Report-To headers
 * needed for CSP level 3 client-side reporting.
 */
class AddReportingHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (count(config('violations.endpoints')) > 0) {
            $response->header('Reporting-Endpoints', Violation::reportingEndpointsHeaderValue());
            $response->header('Report-To', Violation::reportToHeaderValue());
        }

        return $response;
    }
}
