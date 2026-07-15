<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

use Synchro\Violation\Enums\SecurityPolicyViolationEventDisposition;

/**
 * DTO representing the body of a connection-allowlist report sent to
 * an endpoint referenced by a report-to directive endpoint.
 * https://wicg.github.io/connection-allowlists/#reporting
 */
class ConnectionAllowlistReportBody extends ReportBody
{
    public function __construct(
        // The environment's creation URL.
        public readonly string $url = '',
        // If resource URL is a URL, then resource URL, stripped for use in reports. Otherwise, resource URL.
        public readonly string $connection = '',
        // A new list containing the result of serializing each pattern in the original allowlist.
        public readonly string $allowlist = '',
        // The report disposition, either `enforce` or `report`.
        // Use the enum from CSP, since it's the same
        public readonly SecurityPolicyViolationEventDisposition $disposition = SecurityPolicyViolationEventDisposition::Enforce,
    ) {}
}
