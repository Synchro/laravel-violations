<?php

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Presets\Basic;
use Synchro\Violation\Support\AddReportingEndpointsPreset;
use Synchro\Violation\Violation;

it('adds the CSP report-uri and report-to clauses to the policy directly', function () {
    $policy = new Policy;
    $policy->add(Directive::REPORT, Violation::cspReportUri());
    $policy->add(Directive::REPORT_TO, Violation::cspReportTo());

    expect($policy->getContents())
        ->toBeString()
        ->toContain('report-uri '.Violation::cspReportUri())
        ->toContain('report-to '.Violation::cspReportTo());
});

it('adds the CSP report-uri and report-to to the policy using a preset', function () {
    $policy = new Policy;
    $preset = new AddReportingEndpointsPreset;
    $preset->configure($policy);

    expect($policy->getContents())
        ->toBeString()
        ->toContain('report-uri '.Violation::cspReportUri())
        ->toContain('report-to '.Violation::cspReportTo());
});

it('injects a report-uri directive into a Spatie CSP header using a preset', function () {
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

    $request = Request::create('/');
    $response = $middleware->handle($request, function ($request) {
        return response('', 200);
    });

    expect($response->headers->has('Content-Security-Policy'))
        ->toBeTrue()
        ->and($response->headers->get('Content-Security-Policy'))
        ->toBeString()
        ->toContain('report-uri '.Violation::cspReportUri())
        ->toContain('report-to '.Violation::cspReportTo());
});
