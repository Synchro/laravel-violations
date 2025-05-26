<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Presets\Basic;
use Synchro\Violation\Http\Middleware\AddReportingHeaders;
use Synchro\Violation\Support\AddReportingEndpointsPreset;

it('adds reporting headers when endpoints are configured', function () {
    Config::set('violations.endpoints', [
        [
            'name' => 'csp',
            'url' => url('violation/csp'),
            'max_age' => 86400, // 1 day
            'type' => 'csp',
        ],
        [
            'name' => 'nel',
            'url' => url('violation/nel'),
            'max_age' => 86400, // 1 day
            'type' => 'nel',
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
        ->toBe('csp='.url('violation/csp').' nel='.url('violation/nel'))
        ->and($response->headers->has('Report-To'))
        ->toBeTrue()
        ->and($response->headers->get('Report-To'))
        ->toBe('[{"group":"csp","max_age":86400,"endpoints":[{"url":'
               .json_encode(url('violation/csp')).'}]},{"group":"nel","max_age":86400,"endpoints":[{"url":'
               .json_encode(url('violation/nel')).'}]}]'
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

it('injects a report-uri directive into a Spatie CSP header', function () {
    Config::set('csp.report_uri', 'http://localhost/violation/csp');
    Config::set('csp.enabled', true);
    Config::set('csp.presets', [
        Basic::class,
        AddReportingEndpointsPreset::class,
    ]);
    Config::set('csp.directives', [
        [Directive::DEFAULT, Keyword::NONE],
        [Directive::SCRIPT, Keyword::SELF],
    ]);
    Config::set('csp.report_only_presets', []);
    Config::set('csp.report_only_directives', []);
    Config::set('csp.nonce_enabled', false);

    $middleware = new Spatie\Csp\AddCspHeaders;

    $request = Request::create('/', 'GET');
    $response = $middleware->handle($request, function ($request) {
        return response('', 200);
    });
    expect($response->headers->has('Content-Security-Policy'))
        ->toBeTrue()
        ->and($response->headers->get('Content-Security-Policy'))
        ->toBeString()
        ->toContain('report-uri '.\Synchro\Violation\Violation::getCspReportUri())
        ->toContain('report-to '.\Synchro\Violation\Violation::getCspReportTo());
});
