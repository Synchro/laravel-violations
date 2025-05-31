<?php

use Illuminate\Support\Facades\Event;
use Synchro\Violation\Events\Violation as ViolationEvent;

it('fires a Violation event when a CSP report is received', function () {
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
            'CONTENT_TYPE' => 'application/csp-report',
            'CONTENT_LENGTH' => strlen($reportData),
        ],
        $reportData
    );

    $response->assertStatus(204);

    Event::assertDispatched(ViolationEvent::class, function ($event) {
        return $event->violation !== null;
    });
});

it('fires a Violation event when an NEL report is received', function () {
    $this->withoutExceptionHandling();
    Event::fake();

    $report = [
        'type' => 'network-error',
        'age' => 29,
        'url' => 'https://example.com/thing.js',
        'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:60.0) Gecko/20100101 Firefox/60.0',
        'body' => [
            'referrer' => 'https://www.example.com/',
            'protocol' => 'xyz',
            'status-code' => 323,
            'elapsed-time' => 143,
            'age' => 5,
            'type' => 'http.dns.name_not_resolved',
        ],
    ];

    $reportData = json_encode($report);

    $response = $this->call(
        'POST',
        '/violations/nel',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/reports+json',
            'CONTENT_LENGTH' => strlen($reportData),
        ],
        $reportData
    );

    $response->assertStatus(204);

    Event::assertDispatched(ViolationEvent::class, function ($event) {
        return $event->violation !== null;
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
            'CONTENT_TYPE' => 'application/csp-report',
            'CONTENT_LENGTH' => strlen($reportData),
        ],
        $reportData
    );

    $response->assertStatus(204);
    Event::assertDispatched(ViolationEvent::class);

    // Also verify the event contains the expected data
    Event::assertDispatched(ViolationEvent::class, function ($event) {
        $violation = $event->violation;
        $reportData = json_decode($violation->report, true);

        return $violation->report_source->value === 'report-uri' &&
               $reportData['cspReport']['documentURI'] === 'http://example.org/page.html' &&
               $reportData['cspReport']['blockedURI'] === 'http://evil.example.com/image.png';
    });
});
