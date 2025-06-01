<?php

declare(strict_types=1);

namespace Synchro\Violation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelData\Data;
use Synchro\Violation\Enums\ReportSource;

class ForwardReport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly Data $report,
        private readonly ReportSource $reportSource,
        private readonly string $forwardToUrl,
        private readonly ?string $userAgent = null,
        private readonly ?string $ip = null,
    ) {
        //
    }

    /**
     * @throws ConnectionException
     */
    public function handle(): void
    {
        // Forward the report to the specified URL
        Http::withHeaders([
            'User-Agent' => ($this->userAgent ?? 'Laravel Violation Reporter'),
            'X-Forwarded-For' => (! config('violations.sanitize') && $this->ip ? $this->ip : ''),
        ])->withBody(
            $this->report->toJson(),
            $this->reportSource === ReportSource::REPORT_URI ? 'application/csp-report' :
            'application/reports+json'
        )
            ->post($this->forwardToUrl);
    }
}
