<?php

use Illuminate\Support\Facades\Route;
use Synchro\Violation\Enums\ReportSource;
use Synchro\Violation\Violation;

beforeEach(function () {
    // Set up basic config for tests
    config(['violations.route_prefix' => 'violations']);
    config(['violations.endpoints' => [
        [
            'name' => 'csp',
            'route_suffix' => 'csp',
            'max_age' => 86400,
            'report_source' => ReportSource::REPORT_URI,
        ],
        [
            'name' => 'reports',
            'route_suffix' => 'reports',
            'max_age' => 86400,
            'report_source' => ReportSource::REPORT_TO,
        ],
    ]]);

    // Register the routes so they exist for testing
    Route::violations();
});

it('generates CSP report-uri directive value', function () {
    $value = Violation::cspReportUri();

    expect($value)->toBe('http://localhost/violations/csp');
});

it('generates CSP report-to directive value', function () {
    $value = Violation::cspReportTo();

    expect($value)->toBe('reports');
});

it('generates Reporting-Endpoints header value', function () {
    $value = Violation::reportingEndpointsHeaderValue();

    expect($value)->toBe('csp="http://localhost/violations/csp", reports="http://localhost/violations/reports"');
});

it('generates Report-To header value', function () {
    $value = Violation::reportToHeaderValue();
    $decoded = json_decode($value, true);

    expect($decoded)->toHaveCount(1)
        ->and($decoded[0])->toMatchArray([
            'group' => 'reports',
            'max_age' => 86400,
            'endpoints' => [['url' => 'http://localhost/violations/reports']],
        ]);
});

it('handles multiple REPORT_URI endpoints', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'csp1',
            'route_suffix' => 'csp',
            'report_source' => ReportSource::REPORT_URI,
        ],
        [
            'name' => 'csp2',
            'route_suffix' => 'csp',
            'report_source' => ReportSource::REPORT_URI,
        ],
    ]]);

    $value = Violation::cspReportUri();

    expect($value)->toBe('http://localhost/violations/csp http://localhost/violations/csp');
});

it('handles multiple REPORT_TO endpoints', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'reports1',
            'route_suffix' => 'reports',
            'report_source' => ReportSource::REPORT_TO,
        ],
        [
            'name' => 'reports2',
            'route_suffix' => 'reports',
            'report_source' => ReportSource::REPORT_TO,
        ],
    ]]);

    $value = Violation::cspReportTo();

    expect($value)->toBe('reports1 reports2');
});

it('resolves endpoint URL using route_suffix', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'test',
            'route_suffix' => 'csp',
            'report_source' => ReportSource::REPORT_URI,
        ],
    ]]);

    $value = Violation::cspReportUri();

    expect($value)->toBe('http://localhost/violations/csp');
});

it('resolves endpoint URL using direct route name', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'test',
            'route' => 'violations.csp',
            'report_source' => ReportSource::REPORT_URI,
        ],
    ]]);

    $value = Violation::cspReportUri();

    expect($value)->toBe('http://localhost/violations/csp');
});

it('resolves endpoint URL using string URL', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'test',
            'url' => 'https://example.com/custom-endpoint',
            'report_source' => ReportSource::REPORT_URI,
        ],
    ]]);

    $value = Violation::cspReportUri();

    expect($value)->toBe('https://example.com/custom-endpoint');
});

it('resolves endpoint URL using callable URL', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'test',
            'url' => fn () => 'https://dynamic.example.com/endpoint',
            'report_source' => ReportSource::REPORT_URI,
        ],
    ]]);

    $value = Violation::cspReportUri();

    expect($value)->toBe('https://dynamic.example.com/endpoint');
});

it('respects custom route prefix', function () {
    config(['violations.route_prefix' => 'security-reports']);
    config(['violations.endpoints' => [
        [
            'name' => 'csp',
            'route_suffix' => 'csp',
            'report_source' => ReportSource::REPORT_URI,
        ],
    ]]);

    // Register routes with a custom prefix
    Route::violations('security-reports');

    $value = Violation::cspReportUri();

    expect($value)->toBe('http://localhost/security-reports/csp');
});

it('throws exception for an endpoint without a URL configuration', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'test',
            'report_source' => ReportSource::REPORT_URI,
            // Missing route_suffix, route, or url
        ],
    ]]);

    expect(fn () => Violation::cspReportUri())
        ->toThrow(InvalidArgumentException::class, 'Endpoint must have either "route_suffix", "route", or "url" key');
});

it('returns an empty string when no endpoints match report source', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'reports',
            'route_suffix' => 'reports',
            'report_source' => ReportSource::REPORT_TO,
        ],
    ]]);

    $value = Violation::cspReportUri(); // Looking for REPORT_URI but only REPORT_TO exists

    expect($value)->toBe('');
});

it('returns an empty JSON array when no REPORT_TO endpoints exist', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'csp',
            'route_suffix' => 'csp',
            'report_source' => ReportSource::REPORT_URI,
        ],
    ]]);

    $value = Violation::reportToHeaderValue();

    expect($value)->toBe('[]');
});
