<?php

use Spatie\Csp\Policy;
use Synchro\Violation\Support\AddReportingEndpointsPreset;
use Synchro\Violation\Violation;

it('adds the CSP report-uri and report-to clauses to the policy directly', function () {
    $policy = new Policy;

    $policy->add(\Spatie\Csp\Directive::REPORT, Violation::getCspReportUri());
    $policy->add(\Spatie\Csp\Directive::REPORT_TO, Violation::getCspReportTo());
    expect($policy->getContents())
        ->toBeString()
        ->toContain('report-uri '.Violation::getCspReportUri())
        ->toContain('report-to '.Violation::getCspReportTo());
});

it('adds the CSP report-uri and report-to to the policy using a preset', function () {
    $policy = new Policy;
    $preset = new AddReportingEndpointsPreset;
    $preset->configure($policy);

    expect($policy->getContents())
        ->toBeString()
        ->toContain('report-uri '.Violation::getCspReportUri())
        ->toContain('report-to '.Violation::getCspReportTo());
});
