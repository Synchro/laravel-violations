<?php

it('can create a Report-To header value', function () {
    $reportTo = Synchro\Violation\Violation::reportToHeaderValue();

    expect($reportTo)
        ->toBe('[{"group":"reports","max_age":86400,"endpoints":[{"url":"http:\/\/localhost\/violations\/reports"}]}]');
});

it('can create a Reporting-Endpoints header value', function () {
    $reportTo = Synchro\Violation\Violation::reportingEndpointsHeaderValue();

    expect($reportTo)
        ->toBe('csp="http://localhost/violations/csp", reports="http://localhost/violations/reports"');
});
