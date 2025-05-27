<?php

namespace Synchro\Violation\Commands;

use Illuminate\Console\Command;
use Synchro\Violation\Jobs\ForwardReport;
use Synchro\Violation\Models\Violation;

use function Laravel\Prompts\progress;

class QueueViolations extends Command
{
    public $signature = 'violations:queue';

    public $description = 'Queue any pending violation reports to a report aggregation service.';

    public function handle(): int
    {
        if (config('violations.forward_to') === null) {
            $this->error(__('Violation forwarding URL is not configured; ignoring.'));

            return self::FAILURE;
        }
        $this->info(__('Forwarding violation reports...'));
        $violations = Violation::unforwarded()->get();
        if ($violations->isEmpty()) {
            $this->info(__('No violations to forward.'));

            return self::SUCCESS;
        }
        progress(
            'Queuing reports',
            $violations,
            function ($violation) {
                ForwardReport::dispatch($violation);
            }
        );
        $this->info(__(':count violation report(s) queued for forwarding.', ['count' => $violations->count()]));

        return self::SUCCESS;
    }
}
