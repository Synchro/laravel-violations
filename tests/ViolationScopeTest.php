<?php

use Synchro\Violation\Enums\ReportSource;
use Synchro\Violation\Models\Violation;

beforeEach(function () {
    config(['violations.table' => 'violations']);
    config(['violations.max_forward_attempts' => 3]);
});

it('finds unforwarded violations that have not had any forwarding attempts', function () {
    Violation::factory()->create([
        'forwarded' => false,
        'forward_attempts' => 0,
    ]);

    $violations = Violation::unforwarded()->get();

    expect($violations)->toHaveCount(1);
});

it('finds unforwarded violations with attempts below limit', function () {
    Violation::factory()->create([
        'forwarded' => false,
        'forward_attempts' => 2,
    ]);

    $violations = Violation::unforwarded()->get();

    expect($violations)->toHaveCount(1);
});

it('ignores unforwarded violations that match the attempt limit', function () {
    Violation::factory()->create([
        'forwarded' => false,
        'forward_attempts' => 3,
    ]);

    $violations = Violation::unforwarded()->get();

    expect($violations)->toHaveCount(0);
});

it('ignores violations that exceed the attempt limit', function () {
    Violation::factory()->create([
        'forwarded' => false,
        'forward_attempts' => 5,
    ]);

    $violations = Violation::unforwarded()->get();

    expect($violations)->toHaveCount(0);
});

it('ignores violations that have already been forwarded', function () {
    Violation::factory()->create([
        'forwarded' => true,
        'forward_attempts' => 1,
    ]);

    $violations = Violation::unforwarded()->get();

    expect($violations)->toHaveCount(0);
});

it('respects a custom max_forward_attempts limit value', function () {
    config(['violations.max_forward_attempts' => 5]);

    Violation::factory()->create([
        'forwarded' => false,
        'forward_attempts' => 4,
    ]);

    $violations = Violation::unforwarded()->get();

    expect($violations)->toHaveCount(1);
});

it('filters multiple violations correctly', function () {
    // Should be included
    Violation::factory()->create([
        'forwarded' => false,
        'forward_attempts' => 0,
    ]);

    Violation::factory()->create([
        'forwarded' => false,
        'forward_attempts' => 2,
    ]);

    // Should be excluded
    Violation::factory()->create([
        'forwarded' => true,
        'forward_attempts' => 1,
    ]);

    Violation::factory()->create([
        'forwarded' => false,
        'forward_attempts' => 3,
    ]);

    $violations = Violation::unforwarded()->get();

    expect($violations)->toHaveCount(2);
});

it('casts the report_source enum correctly', function () {
    $violation = Violation::factory()->create([
        'report_source' => ReportSource::REPORT_URI,
    ]);

    expect($violation->report_source)
        ->toBeInstanceOf(ReportSource::class)
        ->and($violation->report_source)->toBe(ReportSource::REPORT_URI);
});

it('casts a report to JSON correctly', function () {
    $reportData = ['test' => 'data', 'nested' => ['key' => 'value']];

    $violation = Violation::factory()->create([
        'report' => $reportData,
    ]);

    expect($violation->report)
        ->toBeArray()
        ->and($violation->report)->toBe($reportData);
});

it('uses a custom table name from config', function () {
    config(['violations.table' => 'custom_violations_table']);

    $violation = new Violation;

    // The table() method returns the config value, but we need to call it via reflection
    // since it's protected, or check the actual table name being used
    $reflection = new ReflectionClass($violation);
    $method = $reflection->getMethod('table');
    $method->setAccessible(true);

    expect($method->invoke($violation))->toBe('custom_violations_table');
});
