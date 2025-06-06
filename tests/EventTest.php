<?php

use Illuminate\Support\Facades\Event;
use Synchro\Violation\Events\Violation as ViolationEvent;
use Synchro\Violation\Reports\CSP2Report;
use Synchro\Violation\Reports\Report;

it('fires a Violation event when a CSP2 report is received', function () {
    Event::fake();

    $report = [
        'csp-report' => [
            'document-uri' => 'http://example.org/page.html',
            'referrer' => 'http://evil.example.com/haxor.html',
            'blocked-uri' => 'http://evil.example.com/image.png',
            'violated-directive' => 'default-src \'self\'',
            'effective-directive' => 'img-src',
            'original-policy' => 'default-src \'self\'; report-uri http://example.org/csp-report.cgi',
        ],
    ];

    $reportData = json_encode($report);

    $response = $this->call(
        'POST',
        '/violations/csp',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => CSP2Report::MIME_TYPE,
            'CONTENT_LENGTH' => strlen($reportData),
        ],
        $reportData,
    );

    $response->assertNoContent();

    Event::assertDispatched(ViolationEvent::class, function ($event) {
        return $event->report !== null && $event->reportSource !== null;
    });
});

it('fires a Violation event when an NEL report is received via a reports endpoint', function () {
    $this->withoutExceptionHandling();
    Event::fake();

    $report = [
        'type' => 'network-error',
        'age' => 29,
        'url' => 'https://example.com/thing.js',
        'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0',
        'body' => [
            'referrer' => 'https://www.example.com/',
            'protocol' => 'h2',
            'status_code' => 0,
            'elapsed_time' => 143,
            'age' => 5,
            'type' => 'dns.name_not_resolved',
            'phase' => 'dns',
            'type' => 'dns.name_not_resolved',
            'sampling_fraction' => 1.0,
        ],
    ];

    $reportData = json_encode($report);

    $response = $this->call(
        'POST',
        '/violations/reports',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => Report::MIME_TYPE,
            'CONTENT_LENGTH' => strlen($reportData),
        ],
        $reportData,
    );

    $response->assertNoContent();

    Event::assertDispatched(ViolationEvent::class, function ($event) {
        return $event->report !== null && $event->reportSource !== null;
    });
});

it('passes the correct violation data in the event', function () {
    $this->withoutExceptionHandling();
    Event::fake();

    $report = [
        'csp-report' => [
            'document-uri' => 'http://example.org/page.html',
            'blocked-uri' => 'http://evil.example.com/image.png',
            'violated-directive' => 'default-src \'self\'',
        ],
    ];

    $reportData = json_encode($report);

    $response = $this->call(
        'POST',
        '/violations/csp',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => CSP2Report::MIME_TYPE,
            'CONTENT_LENGTH' => strlen($reportData),
        ],
        $reportData,
    );

    $response->assertNoContent();
    Event::assertDispatched(ViolationEvent::class);

    // Also verify the event contains the expected data
    Event::assertDispatched(ViolationEvent::class, function ($event) {
        return $event->reportSource->value === 'report-uri' &&
               $event->report->cspReport->documentURI === 'http://example.org/page.html' &&
               $event->report->cspReport->blockedURI === 'http://evil.example.com/image.png';
    });
});
