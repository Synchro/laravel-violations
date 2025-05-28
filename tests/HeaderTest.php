<?php

it('can create a Report-To header value', function () {
    $reportTo = Synchro\Violation\Violation::reportToHeaderValue();

    expect($reportTo)
        ->toBe('[{"group":"csp","max_age":86400,"endpoints":[{"url":"http:\/\/localhost\/violations\/csp"}]},{"group":"nel","max_age":86400,"endpoints":[{"url":"http:\/\/localhost\/violations\/nel"}]}]');
});

it('can create a Reporting-Endpoints header value', function () {
    $reportTo = Synchro\Violation\Violation::reportingEndpointsHeaderValue();

    expect($reportTo)
        ->toBe('csp="http://localhost/violations/csp", nel="http://localhost/violations/nel"');
});
