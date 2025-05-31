<?php

use Synchro\Violation\Enums\ReportSource;

// config for Synchro/Violation
return [
    /*
     * An optional URL to forward the report to, e.g. https://<project>.report-uri.com
     */
    'forward_to' => env('VIOLATIONS_ENDPOINT', null),
    /**
     * Whether to sanitize the report (e.g. removing client IP) before forwarding it
     */
    'sanitize' => (bool) env('VIOLATIONS_SANITIZE', true),
    /**
     * The name of the table to store reports in, if null, nothing is stored
     */
    'table' => env('VIOLATIONS_TABLE', null),
    /**
     * The base URL prefix used when registering routes with Route::violations().
     * This is the single source of truth for route naming and URL generation.
     *
     * Usage:
     * 1. Set your desired prefix in this config
     * 2. Call Route::violations() without parameters (recommended)
     * 3. Routes will be registered with this prefix automatically
     *
     * Examples:
     * - 'route_prefix' => 'violations' → Route::violations() → /violations/csp, /violations/reports
     * - 'route_prefix' => 'security-reports' → Route::violations() → /security-reports/csp, /security-reports/reports
     *
     * Note: You can still override by passing a parameter: Route::violations('custom-name')
     */
    'route_prefix' => 'violations',

    /**
     * The named endpoints to use in CSP, Reporting-Endpoints and Report-To headers
     * Each needs a name and the route suffix (will be combined with route_prefix above).
     * The max-age value is only used in the Report-To header; it is not used in Reporting-Endpoints.
     * The report_source value determines which reporting mechanism the endpoint supports:
     * - ReportSource::REPORT_URI: For the deprecated CSP2 report-uri directive (application/csp-report)
     * - ReportSource::REPORT_TO: For the modern report-to mechanism (application/reports+json) - CSP3, NEL, etc.
     */
    'endpoints' => [
        [
            'name' => 'csp',
            'route_suffix' => 'csp',
            'max_age' => 86400, // 1 day
            'report_source' => ReportSource::REPORT_URI,
        ],
        [
            'name' => 'reports',
            'route_suffix' => 'reports',
            'max_age' => 86400, // 1 day
            'report_source' => ReportSource::REPORT_TO,
        ],
    ],
];
