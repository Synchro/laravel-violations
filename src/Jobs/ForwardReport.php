<?php

declare(strict_types=1);

namespace Synchro\Violation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
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
        private readonly ?string $userAgent = null,
        private readonly ?string $ip = null,
    ) {
        //
    }

    public function handle(): void
    {
        // Forward the report to our configured forward_to endpoint
        $forwardTo = config('violations.forward_to');
        
        if ($forwardTo) {
            // Forward the report as JSON
            Http::withHeaders([
                'Content-Type' => 'application/reports+json',
                'User-Agent' => $this->userAgent,
            ])
            ->post($forwardTo, $this->report->toJson());
        }
    }
}
