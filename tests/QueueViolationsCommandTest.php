<?php

use Illuminate\Support\Facades\Queue;
use Synchro\Violation\Commands\QueueViolations;
use Synchro\Violation\Enums\ReportSource;
use Synchro\Violation\Jobs\ForwardReport;
use Synchro\Violation\Models\Violation;

beforeEach(function () {
    config(['violations.table' => 'violations']);
    config(['violations.forward_enabled' => true]);
    config(['violations.max_forward_attempts' => 3]);
    Queue::fake();
});

it('fails when forwarding is globally disabled', function () {
    config(['violations.forward_enabled' => false]);

    $this->artisan(QueueViolations::class)
        ->expectsOutput('Violation forwarding is globally disabled; ignoring.')
        ->assertExitCode(1);
});

it('fails when no forwarding endpoints are configured', function () {
    config(['violations.endpoints' => []]);

    $this->artisan(QueueViolations::class)
        ->expectsOutput('No forwarding endpoints configured; ignoring.')
        ->assertExitCode(1);
});

it('fails when endpoints have no forward_to URLs', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'csp',
            'route_suffix' => 'csp',
            'report_source' => ReportSource::REPORT_URI,
        ],
    ]]);

    $this->artisan(QueueViolations::class)
        ->expectsOutput('No forwarding endpoints configured; ignoring.')
        ->assertExitCode(1);
});

it('succeeds when no violations exist', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'csp',
            'route_suffix' => 'csp',
            'report_source' => ReportSource::REPORT_URI,
            'forward_to' => 'https://example.com/forward',
        ],
    ]]);

    $this->artisan(QueueViolations::class)
        ->expectsOutput('Forwarding violation reports...')
        ->expectsOutput('No violations to forward.')
        ->assertExitCode(0);
});

it('queues CSP2 violations for forwarding', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'csp',
            'route_suffix' => 'csp',
            'report_source' => ReportSource::REPORT_URI,
            'forward_to' => 'https://example.com/forward',
        ],
    ]]);

    $violation = Violation::factory()->create([
        'report_source' => ReportSource::REPORT_URI,
        'report' => ['csp-report' => ['test' => 'data']],
        'forwarded' => false,
        'forward_attempts' => 0,
    ]);

    $this->artisan(QueueViolations::class)
        ->expectsOutput('Forwarding violation reports...')
        ->expectsOutput('1 violation report(s) queued for forwarding.')
        ->assertExitCode(0);

    Queue::assertPushed(ForwardReport::class, 1);
});

it('queues CSP3 violations for forwarding', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'reports',
            'route_suffix' => 'reports',
            'report_source' => ReportSource::REPORT_TO,
            'forward_to' => 'https://example.com/forward',
        ],
    ]]);

    $violation = Violation::factory()->create([
        'report_source' => ReportSource::REPORT_TO,
        'report' => [
            'type' => 'csp-violation',
            'age' => 10,
            'url' => 'https://example.com/page.html',
            'user_agent' => 'Mozilla/5.0',
            'body' => [
                'blockedURL' => 'https://evil.example.com/script.js',
                'documentURL' => 'https://example.com/page.html',
                'effectiveDirective' => 'script-src',
                'originalPolicy' => "default-src 'none'; script-src 'self'",
                'violatedDirective' => 'script-src',
                'disposition' => 'enforce',
            ],
        ],
        'forwarded' => false,
        'forward_attempts' => 0,
    ]);

    $this->artisan(QueueViolations::class)
        ->expectsOutput('Forwarding violation reports...')
        ->expectsOutput('1 violation report(s) queued for forwarding.')
        ->assertExitCode(0);

    Queue::assertPushed(ForwardReport::class, 1);
});

it('queues NEL violations for forwarding', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'reports',
            'route_suffix' => 'reports',
            'report_source' => ReportSource::REPORT_TO,
            'forward_to' => 'https://example.com/forward',
        ],
    ]]);

    $violation = Violation::factory()->create([
        'report_source' => ReportSource::REPORT_TO,
        'report' => [
            'type' => 'network-error',
            'age' => 29,
            'url' => 'https://example.com/script.js',
            'user_agent' => 'Mozilla/5.0',
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
        ],
        'forwarded' => false,
        'forward_attempts' => 0,
    ]);

    $this->artisan(QueueViolations::class)
        ->expectsOutput('Forwarding violation reports...')
        ->expectsOutput('1 violation report(s) queued for forwarding.')
        ->assertExitCode(0);

    Queue::assertPushed(ForwardReport::class, 1);
});

it('skips violations without matching forwarding endpoint', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'csp',
            'route_suffix' => 'csp',
            'report_source' => ReportSource::REPORT_URI,
            'forward_to' => 'https://example.com/forward',
        ],
    ]]);

    // Create violation with REPORT_TO source, but only REPORT_URI endpoint configured
    Violation::factory()->create([
        'report_source' => ReportSource::REPORT_TO,
        'report' => ['type' => 'csp-violation'],
        'forwarded' => false,
        'forward_attempts' => 0,
    ]);

    $this->artisan(QueueViolations::class)
        ->expectsOutput('Forwarding violation reports...')
        ->expectsOutput('0 violation report(s) queued for forwarding.')
        ->assertExitCode(0);

    Queue::assertNothingPushed();
});

it('skips violations with invalid report data', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'reports',
            'route_suffix' => 'reports',
            'report_source' => ReportSource::REPORT_TO,
            'forward_to' => 'https://example.com/forward',
        ],
    ]]);

    $violation = Violation::factory()->create([
        'report_source' => ReportSource::REPORT_TO,
        'report' => ['invalid' => 'data'], // Missing 'type' field
        'forwarded' => false,
        'forward_attempts' => 0,
    ]);

    $this->artisan(QueueViolations::class)
        ->expectsOutput('Forwarding violation reports...')
        ->expectsOutput("Failed to parse report for violation ID {$violation->id}: Report data must contain a \"type\" field")
        ->expectsOutput('0 violation report(s) queued for forwarding.')
        ->assertExitCode(0);

    Queue::assertNothingPushed();
});

it('handles multiple violations with different configurations', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'csp',
            'route_suffix' => 'csp',
            'report_source' => ReportSource::REPORT_URI,
            'forward_to' => 'https://example.com/csp-forward',
        ],
        [
            'name' => 'reports',
            'route_suffix' => 'reports',
            'report_source' => ReportSource::REPORT_TO,
            'forward_to' => 'https://example.com/reports-forward',
        ],
    ]]);

    // Create CSP2 violation
    Violation::factory()->create([
        'report_source' => ReportSource::REPORT_URI,
        'report' => ['csp-report' => ['test' => 'data']],
        'forwarded' => false,
        'forward_attempts' => 0,
    ]);

    // Create CSP3 violation
    Violation::factory()->create([
        'report_source' => ReportSource::REPORT_TO,
        'report' => [
            'type' => 'csp-violation',
            'age' => 10,
            'url' => 'https://example.com/page.html',
            'body' => ['effectiveDirective' => 'script-src'],
        ],
        'forwarded' => false,
        'forward_attempts' => 1,
    ]);

    $this->artisan(QueueViolations::class)
        ->expectsOutput('Forwarding violation reports...')
        ->expectsOutput('2 violation report(s) queued for forwarding.')
        ->assertExitCode(0);

    Queue::assertPushed(ForwardReport::class, 2);
});

it('respects the unforwarded scope', function () {
    config(['violations.endpoints' => [
        [
            'name' => 'csp',
            'route_suffix' => 'csp',
            'report_source' => ReportSource::REPORT_URI,
            'forward_to' => 'https://example.com/forward',
        ],
    ]]);

    // Should be queued
    Violation::factory()->create([
        'report_source' => ReportSource::REPORT_URI,
        'report' => ['csp-report' => ['test' => 'data']],
        'forwarded' => false,
        'forward_attempts' => 0,
    ]);

    // Should be skipped - already forwarded
    Violation::factory()->create([
        'report_source' => ReportSource::REPORT_URI,
        'report' => ['csp-report' => ['test' => 'data']],
        'forwarded' => true,
        'forward_attempts' => 1,
    ]);

    // Should be skipped - max attempts reached
    Violation::factory()->create([
        'report_source' => ReportSource::REPORT_URI,
        'report' => ['csp-report' => ['test' => 'data']],
        'forwarded' => false,
        'forward_attempts' => 3,
    ]);

    $this->artisan(QueueViolations::class)
        ->expectsOutput('Forwarding violation reports...')
        ->expectsOutput('1 violation report(s) queued for forwarding.')
        ->assertExitCode(0);

    Queue::assertPushed(ForwardReport::class, 1);
});
