<?php

namespace Synchro\Violation\Commands;

use Illuminate\Console\Command;
use Spatie\LaravelData\Data;
use Synchro\Violation\Enums\ReportSource;
use Synchro\Violation\Jobs\ForwardReport;
use Synchro\Violation\Models\Violation;
use Synchro\Violation\Reports\CSP2ReportData;
use Synchro\Violation\Reports\ReportFactory;

use function Laravel\Prompts\progress;

class QueueViolations extends Command
{
    public $signature = 'violations:queue';

    public $description = 'Queue any pending violation reports to their configured forwarding endpoints.';

    public function handle(): int
    {
        if (! config('violations.forward_enabled')) {
            $this->error(__('Violation forwarding is globally disabled; ignoring.'));

            return self::FAILURE;
        }

        $endpoints = config('violations.endpoints', []);
        $forwardingEndpoints = collect($endpoints)->filter(fn (array $endpoint) => ! empty($endpoint['forward_to']))->toArray();

        if (empty($forwardingEndpoints)) {
            $this->error(__('No forwarding endpoints configured; ignoring.'));

            return self::FAILURE;
        }

        $this->info(__('Forwarding violation reports...'));
        $violations = Violation::unforwarded()->get();
        if ($violations->isEmpty()) {
            $this->info(__('No violations to forward.'));

            return self::SUCCESS;
        }

        $queuedCount = 0;
        progress(
            'Queuing reports',
            $violations,
            function (Violation $violation) use (&$queuedCount) {
                $forwardToUrl = $this->getForwardingUrlForReportSource($violation->report_source);

                if ($forwardToUrl) {
                    // Parse the JSON report back to appropriate DTO
                    $report = $this->parseReportToDto($violation);

                    if ($report) {
                        ForwardReport::dispatch(
                            $report,
                            $violation->report_source,
                            $forwardToUrl,
                            $violation->user_agent,
                            $violation->ip,
                            $violation->id
                        );

                        $queuedCount++;
                    }
                }
            }
        );

        $this->info(__(':count violation report(s) queued for forwarding.', ['count' => $queuedCount]));

        return self::SUCCESS;
    }

    /**
     * Get the forwarding URL for a specific report source from the endpoint configuration.
     */
    private function getForwardingUrlForReportSource(ReportSource $reportSource): ?string
    {
        $endpoints = config('violations.endpoints', []);

        foreach ($endpoints as $endpoint) {
            if (isset($endpoint['report_source']) && $endpoint['report_source'] === $reportSource) {
                return $endpoint['forward_to'] ?? null;
            }
        }

        return null;
    }

    /**
     * Convert a stored JSON report back to its appropriate DTO.
     */
    private function parseReportToDto(Violation $violation): ?Data
    {
        try {
            if ($violation->report_source === ReportSource::REPORT_URI) {
                // CSP2 reports are stored differently - need to reconstruct from JSON
                return CSP2ReportData::from(json_encode($violation->report));
            } else {
                // CSP3 and NEL reports can use ReportFactory
                return ReportFactory::from($violation->report);
            }
        } catch (\Exception $e) {
            $this->error(__('Failed to parse report for violation ID :id: :error', [
                'id' => $violation->id,
                'error' => $e->getMessage(),
            ]));

            return null;
        }
    }
}
