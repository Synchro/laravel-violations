<?php

use Synchro\Violation\Reports\CSP2ReportData;
use Synchro\Violation\Reports\NELReport;

it('parses a CSP2 report', function () {
    // Example report from https://www.w3.org/TR/CSP2/#example-violation-report
    $report = json_decode(
        '{
  "csp-report": {
    "document-uri": "http://example.org/page.html",
    "referrer": "http://evil.example.com/haxor.html",
    "blocked-uri": "http://evil.example.com/image.png",
    "violated-directive": "default-src \'self\'",
    "effective-directive": "img-src",
    "original-policy": "default-src \'self\'; report-uri http://example.org/csp-report.cgi"
  }
}',
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    $data = CSP2ReportData::from($report);

    expect($data->cspReport->documentUri)->toBe('http://example.org/page.html');
    expect($data->cspReport->violatedDirective)->toBe("default-src 'self'");
});

it('parses an NEL report', function () {
    $report = json_decode(
        '{
  "type": "nel",
  "age": 29,
  "url": "https://example.com/thing.js",
  "user_agent": "Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0",
  "body": {
    "referrer": "https://www.example.com/",
    "server-ip": "192.168.0.123",
    "protocol": "",
    "status-code": 0,
    "elapsed-time": 143,
    "age": 0,
    "type": "http.dns.name_not_resolved"
  }
}',
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    $data = NELReport::from($report);

    expect($data->body->referrer)->toBe('https://www.example.com/');
    expect($data->body->serverIp)->toBe('192.168.0.123');
});
