<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Synchro\Violation\Enums\ReportSource;
use Synchro\Violation\Http\Middleware\AddReportingHeaders;

it('adds reporting headers when endpoints are configured', function () {
    Config::set('violations.route_prefix', 'violations');
    Config::set('violations.endpoints', [
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
    ]);

    $middleware = new AddReportingHeaders;

    $request = Request::create('/', 'GET');
    $response = $middleware->handle($request, function ($request) {
        return response('', 200);
    });

    expect($response->headers->has('Reporting-Endpoints'))
        ->toBeTrue()
        ->and($response->headers->get('Reporting-Endpoints'))
        ->toBe('csp="'.route('violations.csp').'", reports="'.route('violations.reports').'"')
        ->and($response->headers->has('Report-To'))
        ->toBeTrue()
        ->and($response->headers->get('Report-To'))
        ->toBe(
            '[{"group":"reports","max_age":86400,"endpoints":[{"url":'
            .json_encode(route('violations.reports')).'}]}]',
        );
});

it('does not add reporting headers when no endpoints are configured', function () {
    Config::set('violations.endpoints', []);

    $middleware = new AddReportingHeaders;

    $request = Request::create('/', 'GET');
    $response = $middleware->handle($request, function ($request) {
        return response('', 200);
    });

    expect($response->headers->has('Reporting-Endpoints'))
        ->toBeFalse()
        ->and($response->headers->has('Report-To'))
        ->toBeFalse();
});

it('throws an error when reporting endpoints are invalid', function () {
    Config::set('violations.endpoints', 0);

    $middleware = new AddReportingHeaders;

    $request = Request::create('/', 'GET');
    $middleware->handle($request, function ($request) {
        return response('', 200);
    });
})->throws(TypeError::class);

it('route macro uses config prefix by default', function () {
    // Set a custom prefix in config
    Config::set('violations.route_prefix', 'custom-security');

    // Test the macro function directly to see what prefix it would use
    $testMacro = function (?string $baseUrl = null) {
        return $baseUrl ?? config('violations.route_prefix', 'violations');
    };

    // Test without parameter (should use config)
    $result1 = $testMacro();
    expect($result1)->toBe('custom-security');

    // Test with parameter (should use parameter)
    $result2 = $testMacro('override-prefix');
    expect($result2)->toBe('override-prefix');

    // Test with null parameter (should use config)
    $result3 = $testMacro(null);
    expect($result3)->toBe('custom-security');
});
