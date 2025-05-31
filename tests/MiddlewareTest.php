<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Synchro\Violation\Http\Middleware\AddReportingHeaders;
use Synchro\Violation\Enums\ReportSource;

it('adds reporting headers when endpoints are configured', function () {
    Config::set('violations.endpoints', [
        [
            'name' => 'csp',
            'url' => url('csp'),
            'max_age' => 86400, // 1 day
            'report_source' => ReportSource::REPORT_URI,
        ],
        [
            'name' => 'reports',
            'url' => url('reports'),
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
        ->toBe('csp="'.url('csp').'", reports="'.url('reports').'"')
        ->and($response->headers->has('Report-To'))
        ->toBeTrue()
        ->and($response->headers->get('Report-To'))
        ->toBe(
            '[{"group":"reports","max_age":86400,"endpoints":[{"url":'
            .json_encode(url('reports')).'}]}]',
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
