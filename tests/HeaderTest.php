<?php

it('can create a Report-To header value', function () {
    $reportTo = Synchro\Violation\Violation::getReportToHeaderValue();

    expect($reportTo)
        ->toBe('[{"group":"csp","max_age":86400,"endpoints":[{"url":"http:\/\/localhost\/violation\/csp"}]},{"group":"nel","max_age":86400,"endpoints":[{"url":"http:\/\/localhost\/violation\/nel"}]}]');
});

it('can create a Reporting-Endpoints header value', function () {
    $reportTo = Synchro\Violation\Violation::getReportingEndpointsHeaderValue();

    expect($reportTo)
        ->toBe('csp=http://localhost/violation/csp nel=http://localhost/violation/nel');
});
