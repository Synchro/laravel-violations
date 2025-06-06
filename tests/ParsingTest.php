<?php

use Synchro\Violation\Enums\NELPhase;
use Synchro\Violation\Enums\NetworkReportingReportType;
use Synchro\Violation\Reports\CSP2Report;
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

    $data = CSP2Report::from($report);

    expect($data->cspReport->documentURI)
        ->toBe('http://example.org/page.html')
        ->and($data->cspReport->referrer)->toBe('http://evil.example.com/haxor.html')
        ->and($data->cspReport->blockedURI)->toBe('http://evil.example.com/image.png')
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
    "documentURL": "https://example.com/page2",
    "blockedURL": "https://evil.com/script.js",
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
        ->and($data->body->documentURL)->toBe('https://example.com/page2')
        ->and($data->body->blockedURL)->toBe('https://evil.com/script.js')
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
  "destination": "https://example.com/report",
  "timestamp": 1700000000,
  "attempts": 1,
  "body": {
    "sampling_fraction": 1.0,
    "elapsed_time": 143,
    "age": 5,
    "phase": "dns",
    "type": "http.dns.name_not_resolved",
    "referrer": "https://www.example.com/",
    "server_ip": "192.168.0.123",
    "protocol": "xyz",
    "method": "GET",
    "status_code": 323,
    "url": "https://example.com/thing.js",
    "request_headers": {
      "User-Agent": ["Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0"]
    },
    "response_headers": {
      "Content-Type": ["application/javascript"]
    }
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
        ->and($data->destination)->toBe('https://example.com/report')
        ->and($data->timestamp)->toBe(1700000000)
        ->and($data->attempts)->toBe(1)
        ->and($data->body->samplingFraction)->toBe(1.0)
        ->and($data->body->referrer)->toBe('https://www.example.com/')
        ->and($data->body->serverIp)->toBe('192.168.0.123')
        ->and($data->body->protocol)->toBe('xyz')
        ->and($data->body->method)->toBe('GET')
        ->and($data->body->statusCode)->toBe(323)
        ->and($data->body->elapsedTime)->toBe(143)
        ->and($data->body->age)->toBe(5)
        ->and($data->body->type)->toBe('http.dns.name_not_resolved')
        ->and($data->body->phase)->toBe(NELPhase::DNS)
        ->and($data->body->url)->toBe('https://example.com/thing.js')
        ->and($data->body->requestHeaders)->toEqual([
            'User-Agent' => ['Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0'],
        ])
        ->and($data->body->responseHeaders)->toEqual([
            'Content-Type' => ['application/javascript'],
        ]);
});

it('can reconstruct an NEL report', function () {
    $report = '{
  "type": "network-error",
  "age": 29,
  "url": "https://example.com/thing.js",
  "user_agent": "Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0",
  "destination": "https://example.com/report",
  "timestamp": 1700000000,
  "attempts": 1,
  "body": {
    "sampling_fraction": 1.0,
    "elapsed_time": 143,
    "age": 5,
    "phase": "dns",
    "type": "http.dns.name_not_resolved",
    "referrer": "https://www.example.com/",
    "server_ip": "192.168.0.123",
    "protocol": "xyz",
    "method": "GET",
    "status_code": 323,
    "url": "https://example.com/thing.js",
    "request_headers": {
      "User-Agent": ["Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0"]
    },
    "response_headers": {
      "Content-Type": ["application/javascript"]
    }
  }
}';
    $data = ReportFactory::from(json_decode($report, true, 512, JSON_THROW_ON_ERROR));
    $reconstructed = $data->toArray();
    // We don't necessarily expect everything in the submitted report to be present in our reconstructed one,
    // but we do expect the reconstructed one to be a subset of the original report
    expect($reconstructed)
        ->toBeASubsetOf(json_decode($report, true, 512, JSON_THROW_ON_ERROR));
});
