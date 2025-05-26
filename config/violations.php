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
     * The name of the table to store reports in, if null, nothing is stored
     */
    'table' => env('VIOLATIONS_TABLE', null),
    /**
     * The named endpoints to use in CSP, Reporting-Endpoints and Report-To headers
     * Each needs a name and a URL, which may or may not be an endpoint in the host app
     */
    'endpoints' => [
        [
            'name' => 'csp',
            'url' => url('csp'),
            'max_age' => 86400, // 1 day
            'type' => 'csp',
        ],
        [
            'name' => 'nel',
            'url' => url('nel'),
            'max_age' => 86400, // 1 day
            'type' => 'nel',
        ],
    ],
];
