<?php

use Illuminate\Support\Facades\Route;
use Synchro\Violation\Http\Controllers\ViolationController;

it('can serve and OPTIONS request', function () {
    // Mock the request to simulate an OPTIONS request
    $this->withoutExceptionHandling(); // Optional: To see any exceptions thrown

    // Ensure the middleware is applied
    // $this->withMiddleware(\Synchro\Violation\Http\Middleware\AddReportingEndpointsHeader::class);

    // Register a route for OPTIONS requests
    Route::options('/csp/options', [ViolationController::class, 'options']);

    // Make an OPTIONS request to this route
    $response = $this->call('OPTIONS', '/csp/options');

    expect($response->status())
        ->toBe(204)
        ->and($response->headers->get('Access-Control-Allow-Methods'))
        ->toBe('OPTIONS')
        ->and($response->headers->get('Access-Control-Allow-Origin'))
        ->toBe('*');
});

it('can receive a CSP report-uri report', function () {
    // Mock the request to simulate a CSP violation report
    $this->withoutExceptionHandling(); // Optional: To see any exceptions thrown

    // Ensure the middleware is applied
    // $this->withMiddleware(\Synchro\Violation\Http\Middleware\AddReportingEndpointsHeader::class);

    // Register a route for receiving CSP reports
    Route::post('/csp/reports', [ViolationController::class, 'csp']);

    // Make a POST request to this route with the CSP content type
    $reportData =
        [
            'csp-report' => [
                'blocked-uri' => 'https://evil.example.com/script.js',
                'document-uri' => 'https://example.com/page.html',
                'effective-directive' => 'script-src',
                'original-policy' => "default-src 'none'; script-src 'self'",
                'referrer' => '',
                'status-code' => 200,
                'violated-directive' => 'script-src',
                'source-file' => '',
                'line-number' => 1,
                'column-number' => 1,
            ],
        ];
    $response = $this->call(
        'POST',
        '/csp/reports',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/csp-report',
            'CONTENT_LENGTH' => strlen(json_encode($reportData)),
            'HTTP_ACCEPT' => '*/*',
        ],
        json_encode($reportData)
    );

    expect($response->status())->toBe(204);
    expect($response->content())->toBeEmpty();
});
