<?php

use Synchro\Violation\Enums\NetworkReportingReportType;
use Synchro\Violation\Reports\CSP2ReportData;
use Synchro\Violation\Reports\ReportFactory;

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

    expect($data->cspReport->documentUri)
        ->toBe('http://example.org/page.html')
        ->and($data->cspReport->referrer)->toBe('http://evil.example.com/haxor.html')
        ->and($data->cspReport->blockedUri)->toBe('http://evil.example.com/image.png')
        ->and($data->cspReport->violatedDirective)->toBe("default-src 'self'")
        ->and($data->cspReport->effectiveDirective)->toBe('img-src')
        ->and($data->cspReport->originalPolicy)->toBe("default-src 'self'; report-uri http://example.org/csp-report.cgi");
});

it('parses a CSP3 report', function () {
    $report = json_decode(
        '{
  "age": 5,
  "type": "csp-violation",
  "url": "https://example.com/page1",
  "user_agent": "Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0",
  "body": {
    "documentURI": "https://example.com/page2",
    "blockedURI": "https://evil.com/script.js",
    "violatedDirective": "script-src \'self\'",
    "effectiveDirective": "img-src \'self\'",
    "originalPolicy": "script-src \'self\'; report-to csp-endpoint"
  }
}',
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    $data = ReportFactory::from($report);
    expect($data->type)
        ->toBe(NetworkReportingReportType::CSP)
        ->and($data->age)->toBe(5)
        ->and($data->url)->toBe('https://example.com/page1')
        ->and($data->userAgent)->toBe('Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0')
        ->and($data->body->documentUri)->toBe('https://example.com/page2')
        ->and($data->body->blockedUri)->toBe('https://evil.com/script.js')
        ->and($data->body->violatedDirective)->toBe('script-src \'self\'')
        ->and($data->body->effectiveDirective)->toBe('img-src \'self\'')
        ->and($data->body->originalPolicy)->toBe('script-src \'self\'; report-to csp-endpoint');
});

it('parses an NEL report', function () {
    $report = json_decode(
        '{
  "type": "network-error",
  "age": 29,
  "url": "https://example.com/thing.js",
  "user_agent": "Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0",
  "body": {
    "referrer": "https://www.example.com/",
    "server-ip": "192.168.0.123",
    "protocol": "xyz",
    "status-code": 323,
    "elapsed-time": 143,
    "age": 5,
    "type": "http.dns.name_not_resolved"
  }
}',
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    $data = ReportFactory::from($report);
    expect($data->type)
        ->toBe(NetworkReportingReportType::NEL)
        ->and($data->age)->toBe(29)
        ->and($data->url)->toBe('https://example.com/thing.js')
        ->and($data->userAgent)->toBe('Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0')
        ->and($data->body->referrer)->toBe('https://www.example.com/')
        ->and($data->body->serverIp)->toBe('192.168.0.123')
        ->and($data->body->protocol)->toBe('xyz')
        ->and($data->body->statusCode)->toBe(323)
        ->and($data->body->elapsedTime)->toBe(143)
        ->and($data->body->age)->toBe(5)
        ->and($data->body->type)->toBe('http.dns.name_not_resolved');
});
