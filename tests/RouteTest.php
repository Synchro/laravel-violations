<?php

use Symfony\Component\HttpFoundation\Response;
use Synchro\Violation\Http\Controllers\ViolationController;

it('can serve an OPTIONS request', function () {
    $this->withoutExceptionHandling();
    $response = $this->call('OPTIONS', action([ViolationController::class, 'csp']));
    expect($response->status())
        ->toBe(204)
        ->and($response->headers->get('Access-Control-Allow-Methods'))
        ->toBe('OPTIONS')
        ->and($response->headers->get('Access-Control-Allow-Origin'))
        ->toBe('*');
});

it('can receive a CSP report-uri report', function () {
    $this->withoutExceptionHandling();
    $report =
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
    $reportData = json_encode($report);
    $response = $this->call(
        'POST',
        action([ViolationController::class, 'csp']),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/csp-report',
            'CONTENT_LENGTH' => strlen($reportData),
            'HTTP_ACCEPT' => '*/*',
        ],
        $reportData,
    );

    expect($response->status())
        ->toBe(204)
        ->and($response->content())
        ->toBeEmpty();
});

it('rejects invalid JSON', function () {
    $response = $this->call(
        'POST',
        action([ViolationController::class, 'csp']),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/csp-report',
            'CONTENT_LENGTH' => 0,
            'HTTP_ACCEPT' => '*/*',
        ],
        '{"name": "csp",',
    );

    expect($response->status())->toBe(400);
});

it('rejects prohibited HTTP verbs', function () {
    $endpoint = action([ViolationController::class, 'csp']);
    $response = $this->call('GET', $endpoint);
    expect($response->status())->toBe(Response::HTTP_METHOD_NOT_ALLOWED);
    $response = $this->call('PUT', $endpoint);
    expect($response->status())->toBe(Response::HTTP_METHOD_NOT_ALLOWED);
    $response = $this->call('PATCH', $endpoint);
    expect($response->status())->toBe(Response::HTTP_METHOD_NOT_ALLOWED);
    $response = $this->call('DELETE', $endpoint);
    expect($response->status())->toBe(Response::HTTP_METHOD_NOT_ALLOWED);
});
