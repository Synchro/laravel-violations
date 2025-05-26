<?php

namespace Synchro\Violation\Http\Middleware;

use Closure;
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);

        if (count(config('violations.endpoints')) > 0) {
            $response->header('Reporting-Endpoints', Violation::getReportingEndpointsHeaderValue());
            $response->header('Report-To', Violation::getReportToHeaderValue());
        }

        return $response;
    }
}
