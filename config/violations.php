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
     * The named endpoints to use in CSP, Reporting-Endpoints and Report-To headers
     * Each needs a name and a URL, which may or may not be an endpoint in the host app
     * The max-age value is only used in the Report-To header; it is not used in Reporting-Endpoints.
     * The report_source value determines which reporting mechanism the endpoint supports:
     * - 'report-uri': For the deprecated CSP2 report-uri directive (application/csp-report)
     * - 'report-to': For the modern report-to mechanism (application/reports+json) - CSP3, NEL, etc.
     * If you change the URL prefix passed to the route macro in the service provider, you will need to update the route names here to match.
     */
    'endpoints' => [
        [
            'name' => 'csp',
            'url' => fn () => route('violations.csp'),
            'max_age' => 86400, // 1 day
            'report_source' => ReportSource::REPORT_URI,
        ],
        [
            'name' => 'reports',
            'url' => fn () => route('violations.reports'),
            'max_age' => 86400, // 1 day
            'report_source' => ReportSource::REPORT_TO,
        ],
    ],
];
