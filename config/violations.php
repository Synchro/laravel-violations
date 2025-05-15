<?php

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
     * Whether to store reports in the database (you may want to forward only)
     */
    'use_database' => (bool) env('VIOLATIONS_USE_DATABASE', false),
    /**
     * The name of the table to store reports in
     */
    'table_name' => env('VIOLATIONS_DATABASE_TABLE', 'violations'),
    /**
     * The named endpoints to include in the `Reporting-Endpoints` header
     * Each needs a name and a URL, which may or may not be an endpoint in the host app
     */
    'endpoints' => [
        'csp-report' => url('violation/csp'),
        'nel-report' => url('violation/nel'),
    ],
];
