<?php

declare(strict_types=1);

namespace Synchro\Violation\Reports;

/**
 * DTO representing the body of a permissions-policy-violation report sent to
 * an endpoint referenced by a report-to Permissions-Policy directive.
 *
 * @see https://www.w3.org/TR/permissions-policy/#reporting
 */
class PermissionsPolicyReportBody extends ReportBody
{
    public function __construct(
        // The permissions policy element that was violated.
        public readonly string $policyId = '',
        // An explanation of the error.
        public readonly string $message = '',
        // The URL of the file in which the violation occurred. May be null or empty if the triggering script is inline.
        public readonly string $sourceFile = '',
        // The line number in the source file where the violation occurred.
        public readonly int $lineNumber = 0,
        // The column number in the source file where the violation occurred.
        public readonly int $columnNumber = 0,
        // Whether the permissions policy was in enforcing or reporting-only mode.
        public readonly string $disposition = '',
    ) {}
}
