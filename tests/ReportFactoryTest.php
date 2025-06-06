<?php

use Synchro\Violation\Enums\NetworkReportingReportType;
use Synchro\Violation\Reports\CSP3ViolationReport;
use Synchro\Violation\Reports\NELReport;
use Synchro\Violation\Reports\ReportFactory;

it('creates a NEL report from network-error data', function () {
    $data = [
        'type' => 'network-error',
        'age' => 29,
        'url' => 'https://example.com/script.js',
        'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0',
        'body' => [
            'referrer' => 'https://www.example.com/',
            'protocol' => 'h2',
            'status_code' => 0,
            'elapsed_time' => 143,
            'age' => 5,
            'phase' => 'dns',
            'type' => 'dns.name_not_resolved',
            'sampling_fraction' => 1.0,
        ],
    ];

    $report = ReportFactory::from($data);

    expect($report)
        ->toBeInstanceOf(NELReport::class)
        ->and($report->type)->toBe(NetworkReportingReportType::NEL)
        ->and($report->age)->toBe(29)
        ->and($report->url)->toBe('https://example.com/script.js')
        ->and($report->body->type)->toBe('dns.name_not_resolved');
});

it('creates a CSP violation report from csp-violation data', function () {
    $data = [
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

    $report = ReportFactory::from($data);

    expect($report)
        ->toBeInstanceOf(CSP3ViolationReport::class)
        ->and($report->type)->toBe(NetworkReportingReportType::CSP)
        ->and($report->age)->toBe(10)
        ->and($report->url)->toBe('https://example.com/page.html')
        ->and($report->body->effectiveDirective)->toBe('script-src');
});

it('throws an exception when the type field is missing', function () {
    $data = [
        'age' => 10,
        'url' => 'https://example.com/page.html',
        'body' => [],
    ];

    expect(fn () => ReportFactory::from($data))
        ->toThrow(InvalidArgumentException::class, 'Report data must contain a "type" field');
});

it('throws an exception for an unsupported report type', function () {
    $data = [
        'type' => 'unsupported-type',
        'age' => 10,
        'url' => 'https://example.com/page.html',
        'body' => [],
    ];

    expect(fn () => ReportFactory::from($data))
        ->toThrow(InvalidArgumentException::class, 'Unsupported report type: unsupported-type');
});
