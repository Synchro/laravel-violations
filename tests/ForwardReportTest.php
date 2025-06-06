<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Synchro\Violation\Enums\ReportSource;
use Synchro\Violation\Jobs\ForwardReport;
use Synchro\Violation\Reports\CSP2Report;
use Synchro\Violation\Reports\NELReport;

beforeEach(function () {
    config(['violations.table' => 'violations']);
    Http::fake();
    Queue::fake();
});

it('forwards a CSP report correctly', function () {
    $originalJson = '{"csp-report":{"document-uri":"http://example.org/page.html","blocked-uri":"http://evil.example.com/image.png","violated-directive":"default-src \'self\'"}}';
    $report = CSP2Report::from($originalJson);

    $job = new ForwardReport(
        report: $report,
        reportSource: ReportSource::REPORT_URI,
        forwardToUrl: 'https://example.com/reports',
        userAgent: 'Mozilla/5.0 (Test Browser)',
        ip: '192.168.1.1'
    );
    $job->handle();

    Http::assertSentCount(1);
});

it('forwards an NEL report correctly', function () {
    $originalJson = '{
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
    $report = NELReport::from($originalJson);

    $job = new ForwardReport(
        report: $report,
        reportSource: ReportSource::REPORT_TO,
        forwardToUrl: 'https://example.com/reports',
        userAgent: 'Mozilla/5.0 (Test Browser)',
        ip: '2001:db8::1'
    );
    $job->handle();

    Http::assertSentCount(1);
    Http::assertSent(function ($request) {
        return $request->url() === 'https://example.com/reports' &&
               $request->header('Content-Type')[0] === 'application/reports+json';
    });
});

it('handles a null user agent', function () {
    $originalJson = '{"csp-report":{"document-uri":"http://example.org/"}}';
    $report = CSP2Report::from($originalJson);

    $job = new ForwardReport(
        report: $report,
        reportSource: ReportSource::REPORT_URI,
        forwardToUrl: 'https://example.com/reports',
        userAgent: null,
        ip: '192.168.1.1'
    );
    $job->handle();

    Http::assertSentCount(1);
});

it('works without database storage', function () {
    config(['violations.table' => null]);

    $originalJson = '{"csp-report":{"document-uri":"http://example.org/"}}';
    $report = CSP2Report::from($originalJson);

    $job = new ForwardReport(
        report: $report,
        reportSource: ReportSource::REPORT_URI,
        forwardToUrl: 'https://example.com/reports',
        userAgent: 'Mozilla/5.0 (Test Browser)',
        ip: '192.168.1.1'
    );
    $job->handle();

    Http::assertSentCount(1);
});

it('forwards to a specified URL', function () {
    $originalJson = '{"csp-report":{"document-uri":"http://example.org/"}}';
    $report = CSP2Report::from($originalJson);

    $job = new ForwardReport(
        report: $report,
        reportSource: ReportSource::REPORT_URI,
        forwardToUrl: 'https://custom.example.com/endpoint',
        userAgent: 'Mozilla/5.0 (Test Browser)',
        ip: '192.168.1.1'
    );
    $job->handle();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://custom.example.com/endpoint';
    });
});
