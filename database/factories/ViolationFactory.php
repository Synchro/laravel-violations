<?php

declare(strict_types=1);

namespace Synchro\Violation\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Synchro\Violation\Enums\ReportSource;
use Synchro\Violation\Models\Violation;

/**
 * @extends Factory<Violation>
 */
class ViolationFactory extends Factory
{
    protected $model = Violation::class;

    public function definition(): array
    {
        return [
            'report' => '{"csp-report":{"document-uri":"https://example.com/","referrer":"https://example.com/"}, "violated-directive":"script-src", "effective-directive":"script-src", "original-policy":"default-src \'none\'; script-src \'self\';", "blocked-uri":"https://example.com/script.js", "status-code":200, "source-file":"https://example.com/", "line-number":1, "column-number":1, "script-sample":""}',
            'report_source' => ReportSource::REPORT_TO,
            'user_agent' => $this->faker->userAgent(),
            'ip' => $this->faker->ipv4(),
            'forwarded' => false,
            'forward_attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
