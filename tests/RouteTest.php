<?php

use Symfony\Component\HttpFoundation\Response;
use Synchro\Violation\Http\Controllers\ViolationController;

it('can serve an OPTIONS request for a CSP2 endpoint', function () {
    $this->withoutExceptionHandling();
    $response = $this->call('OPTIONS', action([ViolationController::class, 'csp']));
    expect($response->status())
        ->toBe(204)
        ->and($response->headers->get('Access-Control-Allow-Methods'))
        ->toBe('OPTIONS')
        ->and($response->headers->get('Access-Control-Allow-Origin'))
        ->toBe('*');
});

it('can serve an OPTIONS request for a reports endpoint', function () {
    $this->withoutExceptionHandling();
    $response = $this->call('OPTIONS', action([ViolationController::class, 'reports']));
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

it('can receive an NEL report via a reports endpoint', function () {
    $this->withoutExceptionHandling();
    $report = [
        'type' => 'network-error',
        'age' => 29,
        'url' => 'https://example.com/script.js',
        'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0',
        'body' => [
            'referrer' => 'https://www.example.com/',
            'protocol' => 'h2',
            'status-code' => 0,
            'elapsed-time' => 143,
            'age' => 5,
            'type' => 'http.dns.name_not_resolved',
        ],
    ];
    $reportData = json_encode($report);
    $response = $this->call(
        'POST',
        action([ViolationController::class, 'reports']),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/reports+json',
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

it('can receive a CSP3 report via a reports endpoint', function () {
    $this->withoutExceptionHandling();
    $report = [
        'type' => 'csp-violation',
        'age' => 10,
        'url' => 'https://example.com/page.html',
        'user_agent' => 'Mozilla/5.0',
        'body' => [
            'blockedURL' => 'https://evil.example.com/script.js',
            'documentURL' => 'https://example.com/page.html',
            'effectiveDirective' => 'script-src',
            'originalPolicy' => "default-src 'none'; script-src 'self'",
            'violatedDirective' => 'script-src',
            'disposition' => 'enforce',
        ],
    ];
    $reportData = json_encode($report);
    $response = $this->call(
        'POST',
        action([ViolationController::class, 'reports']),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/reports+json',
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

it('rejects invalid content type for a reports endpoint', function () {
    $response = $this->call(
        'POST',
        action([ViolationController::class, 'reports']),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 0,
            'HTTP_ACCEPT' => '*/*',
        ],
        '{}',
    );

    expect($response->status())->toBe(400);
});

it('rejects invalid JSON for a reports endpoint', function () {
    $response = $this->call(
        'POST',
        action([ViolationController::class, 'reports']),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/reports+json',
            'CONTENT_LENGTH' => 0,
            'HTTP_ACCEPT' => '*/*',
        ],
        '{"type": "network-error",',
    );

    expect($response->status())->toBe(400);
});

it('can receive multiple reports in a single request', function () {
    $this->withoutExceptionHandling();
    $reports = [
        [
            'type' => 'network-error',
            'age' => 29,
            'url' => 'https://example.com/script.js',
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0',
            'body' => [
                'referrer' => 'https://www.example.com/',
                'protocol' => 'h2',
                'status-code' => 0,
                'elapsed-time' => 143,
                'age' => 5,
                'type' => 'http.dns.name_not_resolved',
            ],
        ],
        [
            'type' => 'csp-violation',
            'age' => 10,
            'url' => 'https://example.com/page.html',
            'user_agent' => 'Mozilla/5.0',
            'body' => [
                'blockedURL' => 'https://evil.example.com/script.js',
                'documentURL' => 'https://example.com/page.html',
                'effectiveDirective' => 'script-src',
                'originalPolicy' => "default-src 'none'; script-src 'self'",
                'violatedDirective' => 'script-src',
                'disposition' => 'enforce',
            ],
        ],
    ];
    $reportData = json_encode($reports);
    $response = $this->call(
        'POST',
        action([ViolationController::class, 'reports']),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/reports+json',
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

it('rejects an invalid CSP2 content-type', function () {
    $response = $this->call(
        'POST',
        action([ViolationController::class, 'csp']),
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 0,
            'HTTP_ACCEPT' => '*/*',
        ],
        '{}',
    );

    expect($response->status())->toBe(400);
});
