<?php

declare(strict_types=1);

namespace Synchro\Violation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Synchro\Violation\Models\Violation;

class ForwardReport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Violation $violation)
    {
        //
    }

    public function handle(): void
    {
        // Forward the report to our configured report-uri endpoint
        if (config('violation.forward')) {
            // Forward the report
            $response = Http::withHeaders(
                [
                    'Content-Type' => 'application/reports+json',
                    'User-Agent' => $this->violation->user_agent,
                ]
            )
                ->post(config('violation.report_uri'),
                    $this->violation->report
                );

            // If the report was successfully forwarded, record that in the database so we don't send it again
            if ($response->successful() && config('violations.table')) {
                $this->violation->update(['forwarded' => true]);
            }
        }
    }
}
