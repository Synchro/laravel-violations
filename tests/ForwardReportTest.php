<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Synchro\Violation\Jobs\ForwardReport;
use Synchro\Violation\Enums\ReportSource;
use Synchro\Violation\Reports\CSP2ReportData;
use Synchro\Violation\Reports\NELReport;

beforeEach(function () {
    config(['violations.forward_to' => 'https://example.com/reports']);
    config(['violations.table' => 'violations']);
    Http::fake();
    Queue::fake();
});

it('forwards CSP report correctly', function () {
    $originalJson = '{"csp-report":{"document-uri":"http://example.org/page.html","blocked-uri":"http://evil.example.com/image.png","violated-directive":"default-src \'self\'"}}';
    $report = CSP2ReportData::from($originalJson);

    $job = new ForwardReport(
        report: $report,
        reportSource: ReportSource::REPORT_URI,
        userAgent: 'Mozilla/5.0 (Test Browser)',
        ip: '192.168.1.1'
    );
    $job->handle();

    Http::assertSentCount(1);
});

it('forwards NEL report correctly', function () {
    $originalJson = '{"type":"network-error","age":29,"url":"https://example.com/thing.js","user_agent":"Mozilla/5.0","body":{"type":"http.dns.name_not_resolved","referrer":"https://example.com/"}}';
    $report = NELReport::from($originalJson);

    $job = new ForwardReport(
        report: $report,
        reportSource: ReportSource::REPORT_TO,
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

it('handles null user agent', function () {
    $originalJson = '{"csp-report":{"document-uri":"http://example.org/"}}';
    $report = CSP2ReportData::from($originalJson);

    $job = new ForwardReport(
        report: $report,
        reportSource: ReportSource::REPORT_URI,
        userAgent: null,
        ip: '192.168.1.1'
    );
    $job->handle();

    Http::assertSentCount(1);
});

it('works without database storage', function () {
    config(['violations.table' => null]);

    $originalJson = '{"csp-report":{"document-uri":"http://example.org/"}}';
    $report = CSP2ReportData::from($originalJson);

    $job = new ForwardReport(
        report: $report,
        reportSource: ReportSource::REPORT_URI,
        userAgent: 'Mozilla/5.0 (Test Browser)',
        ip: '192.168.1.1'
    );
    $job->handle();

    Http::assertSentCount(1);
});

it('does not forward when disabled', function () {
    config(['violations.forward_to' => null]);

    $originalJson = '{"csp-report":{"document-uri":"http://example.org/"}}';
    $report = CSP2ReportData::from($originalJson);

    $job = new ForwardReport(
        report: $report,
        reportSource: ReportSource::REPORT_URI,
        userAgent: 'Mozilla/5.0 (Test Browser)',
        ip: '192.168.1.1'
    );
    $job->handle();

    Http::assertNothingSent();
});