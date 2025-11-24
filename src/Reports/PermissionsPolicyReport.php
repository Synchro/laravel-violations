<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Synchro\Violation\Enums\NetworkReportingReportType;

/**
 * Class representing a permissions-policy-violation report sent to a report-to endpoint.
 *
 * @see https://www.w3.org/TR/permissions-policy/#reporting
 */
class PermissionsPolicyReport extends Report
{
    public function __construct(
        // The report type.
        public readonly NetworkReportingReportType $type,
        // The report body containing CSP-specific violation data.
        public readonly PermissionsPolicyReportBody $body,
        // The client's user-agent string
        #[MapName('user_agent')]
        public readonly string $userAgent = '',
        // The number of milliseconds between the time the error occurred and when the report was sent.
        #[Min(0)]
        public readonly int $age = 0,
        // The number of times the client has attempted to send this report.
        #[Min(0)]
        public readonly int $attempts = 0,
        // The address of the document where the violation occurred.
        #[Max(2048)]
        public readonly string $url = '',
    ) {}
}
