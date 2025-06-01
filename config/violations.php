<?php

use Synchro\Violation\Enums\ReportSource;

// config for Synchro/Violation
return [
    /**
     * Whether to sanitize the report (e.g. removing client IP) before forwarding it
     */
    'sanitize' => (bool) env('VIOLATIONS_SANITIZE', true),

    /**
     * Global switch to enable/disable forwarding for all endpoints.
     * When false, no reports will be forwarded regardless of per-endpoint settings.
     */
    'forward_enabled' => (bool) env('VIOLATIONS_FORWARD_ENABLED', true),

    /**
     * Maximum number of forwarding attempts for each violation report.
     * After this number of failed attempts, forwarding will no longer be retried, but the report will remain in the database.
     */
    'max_forward_attempts' => (int) env('VIOLATIONS_MAX_FORWARD_ATTEMPTS', 3),
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
     * The forward_to value is an optional URL to forward reports to for this specific endpoint.
     */
    'endpoints' => [
        [
            'name' => 'csp',
            'route_suffix' => 'csp',
            'max_age' => 86400, // 1 day
            'report_source' => ReportSource::REPORT_URI,
            'forward_to' => env('VIOLATIONS_CSP_FORWARD_TO', null),
        ],
        [
            'name' => 'reports',
            'route_suffix' => 'reports',
            'max_age' => 86400, // 1 day
            'report_source' => ReportSource::REPORT_TO,
            'forward_to' => env('VIOLATIONS_REPORTS_FORWARD_TO', null),
        ],
    ],
];
