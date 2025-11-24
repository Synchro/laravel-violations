<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Synchro\Violation\Enums\NetworkReportingReportType;

/**
 * Class representing a CSP3 hash usage report sent to a report-to endpoint.
 *
 * @see https://w3c.github.io/webappsec-csp/#csp-hash-report
 */
class CSP3HashReport extends Report
{
    public function __construct(
        // The report type
        readonly public NetworkReportingReportType $type,
        // The report body containing CSP-specific violation data
        readonly public CSP3HashReportBody $body,
        // The client's user-agent string
        #[MapName('user_agent')]
        readonly public string $userAgent = '',
        // The number of milliseconds between report generation and the time the error occurred
        #[Min(0)]
        readonly public int $age = 0,
        // The number of times the client has attempted to send this report
        #[Min(0)]
        readonly public int $attempts = 0,
        // The address of the document where the violation occurred
        #[Max(2048)]
        readonly public string $url = '',
    ) {}
}
